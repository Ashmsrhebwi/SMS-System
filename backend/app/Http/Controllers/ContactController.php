<?php

namespace App\Http\Controllers;

use App\Exports\ContactsExport;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Imports\ContactsImport;
use App\Models\Contact;
use App\Models\Segment;
use App\Models\Tag;
use App\Services\ActivityLogger;
use App\Services\AuditLogger;
use App\Services\PhoneNormalizerService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Contact::class);

        $query = Contact::with('tags')->latest();

        if ($tagId = $request->get('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
        }
        if ($optIn = $request->get('opt_in')) {
            $query->where('opted_in', $optIn === '1');
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate(50)->withQueryString();
        $tags     = Tag::orderBy('name')->get();

        return view('contacts.index', compact('contacts', 'tags'));
    }

    public function show(Contact $contact)
    {
        $this->authorize('view', $contact);

        $contact->load(['tags', 'notes.author', 'activities.user']);

        $campaigns = $contact->messages()
            ->with('campaign')
            ->select('campaign_id', 'status', 'created_at')
            ->latest()
            ->get()
            ->groupBy('campaign_id')
            ->map(fn($msgs) => $msgs->first());

        return view('contacts.show', compact('contact', 'campaigns'));
    }

    public function create()
    {
        $this->authorize('create', Contact::class);
        $tags = Tag::orderBy('name')->get();
        return view('contacts.create', compact('tags'));
    }

    public function store(StoreContactRequest $request)
    {
        // Check for duplicate (warn only; controller still creates on explicit confirm)
        if ($request->filled('phone') && !$request->boolean('force_create')) {
            $normalized = app(PhoneNormalizerService::class)->normalize($request->phone);
            if ($normalized && Contact::where('phone', $normalized)->exists()) {
                return back()->withInput()
                    ->with('duplicate_warning', "Phone number {$normalized} already exists.")
                    ->with('force_confirm', true);
            }
            if ($request->filled('email') && Contact::where('email', $request->email)->exists()) {
                return back()->withInput()
                    ->with('duplicate_warning', "Email {$request->email} is already used by another contact.")
                    ->with('force_confirm', true);
            }
        }

        $contact = Contact::create([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'notes'    => $request->notes,
            'opted_in' => $request->boolean('opted_in', true),
        ]);

        if ($request->has('tags')) {
            $contact->tags()->sync($request->tags);
        }

        ActivityLogger::contactCreated($contact);
        AuditLogger::log('create_contact', $contact, null, $contact->only(['name', 'phone', 'email']));

        return redirect()->route('contacts.show', $contact)->with('success', 'Contact created successfully.');
    }

    public function edit(Contact $contact)
    {
        $this->authorize('update', $contact);
        $tags = Tag::orderBy('name')->get();
        return view('contacts.edit', compact('contact', 'tags'));
    }

    public function update(UpdateContactRequest $request, Contact $contact)
    {
        $old = $contact->only(['name', 'phone', 'email', 'opted_in']);

        // Track tag changes for activity log
        $oldTagIds = $contact->tags->pluck('id')->toArray();
        $newTagIds = $request->tags ?? [];

        $contact->update([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'notes'    => $request->notes,
            'opted_in' => $request->boolean('opted_in'),
        ]);

        $contact->tags()->sync($newTagIds);

        // Log tag changes
        $allTags = Tag::whereIn('id', array_unique(array_merge($oldTagIds, $newTagIds)))->get()->keyBy('id');
        foreach (array_diff($newTagIds, $oldTagIds) as $addedId) {
            if ($allTags->has($addedId)) ActivityLogger::tagAdded($contact, $allTags[$addedId]);
        }
        foreach (array_diff($oldTagIds, $newTagIds) as $removedId) {
            if ($allTags->has($removedId)) ActivityLogger::tagRemoved($contact, $allTags[$removedId]);
        }

        ActivityLogger::contactUpdated($contact);
        AuditLogger::log('update_contact', $contact, $old, $contact->fresh()->only(['name', 'phone', 'email', 'opted_in']));

        return redirect()->route('contacts.show', $contact)->with('success', 'Contact updated successfully.');
    }

    public function checkDuplicate(Request $request)
    {
        $phone = $request->get('phone');
        $email = $request->get('email');
        $excludeId = $request->get('exclude_id');

        $result = ['phone_exists' => false, 'email_exists' => false, 'contact' => null];

        if ($phone) {
            $normalized = app(PhoneNormalizerService::class)->normalize($phone);
            if ($normalized) {
                $q = Contact::where('phone', $normalized);
                if ($excludeId) $q->where('id', '!=', $excludeId);
                $existing = $q->first(['id', 'name', 'phone']);
                if ($existing) {
                    $result['phone_exists'] = true;
                    $result['contact'] = ['id' => $existing->id, 'name' => $existing->name];
                }
            }
        }

        if ($email && !$result['phone_exists']) {
            $q = Contact::where('email', $email);
            if ($excludeId) $q->where('id', '!=', $excludeId);
            $existing = $q->first(['id', 'name', 'email']);
            if ($existing) {
                $result['email_exists'] = true;
                $result['contact'] = ['id' => $existing->id, 'name' => $existing->name];
            }
        }

        return response()->json($result);
    }

    public function importForm()
    {
        $this->authorize('import', Contact::class);
        return view('contacts.import');
    }

    public function import(Request $request)
    {
        $this->authorize('import', Contact::class);

        $request->validate([
            'file'             => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'duplicate_action' => 'in:skip,update',
        ]);

        $import = new ContactsImport($request->get('duplicate_action', 'skip'));
        Excel::import($import, $request->file('file'));

        AuditLogger::log('import_contacts', null, null, [
            'imported' => $import->imported,
            'updated'  => $import->updated,
            'skipped'  => $import->skipped,
        ]);

        return redirect()->route('contacts.index')->with('import_result', [
            'imported' => $import->imported,
            'updated'  => $import->updated,
            'skipped'  => $import->skipped,
            'errors'   => $import->errors,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('export', Contact::class);

        $filter    = $request->get('filter', 'all');
        $segmentId = $request->get('segment_id');
        $tagId     = $request->get('tag_id');
        $format    = $request->get('format', 'xlsx');

        $export   = new ContactsExport($filter, $segmentId ? (int) $segmentId : null, $tagId ? (int) $tagId : null);
        $filename = 'contacts-export-' . now()->format('Y-m-d') . '.' . $format;

        AuditLogger::log('export_contacts', null, null, ['filter' => $filter, 'format' => $format]);

        if ($format === 'csv') {
            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
        }
        return Excel::download($export, $filename);
    }

    public function toggleOptIn(Contact $contact)
    {
        $this->authorize('toggleOptIn', $contact);
        $contact->update(['opted_in' => !$contact->opted_in]);
        ActivityLogger::contactUpdated($contact);
        return back()->with('success', 'Contact updated.');
    }

    public function destroy(Contact $contact)
    {
        $this->authorize('delete', $contact);
        AuditLogger::log('delete_contact', $contact, $contact->only(['name', 'phone']));
        $contact->delete();
        return back()->with('success', 'Contact deleted.');
    }
}
