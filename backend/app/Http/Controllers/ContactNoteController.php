<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactNote;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ContactNoteController extends Controller
{
    public function store(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $note = $contact->notes()->create([
            'user_id' => auth()->id(),
            'note'    => $request->note,
        ]);

        ActivityLogger::noteAdded($contact, $request->note);

        if ($request->expectsJson()) {
            return response()->json([
                'note' => $note->load('author'),
            ]);
        }

        return back()->with('success', 'Note added.');
    }

    public function update(Request $request, Contact $contact, ContactNote $note)
    {
        $this->authorize('update', $contact);

        abort_unless($note->contact_id === $contact->id, 403);
        abort_unless($note->user_id === auth()->id() || auth()->user()->isAdmin(), 403);

        $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $note->update(['note' => $request->note]);

        if ($request->expectsJson()) {
            return response()->json(['note' => $note->fresh()->load('author')]);
        }

        return back()->with('success', 'Note updated.');
    }

    public function destroy(Contact $contact, ContactNote $note)
    {
        $this->authorize('update', $contact);

        abort_unless($note->contact_id === $contact->id, 403);
        abort_unless($note->user_id === auth()->id() || auth()->user()->isAdmin(), 403);

        $note->delete();

        return back()->with('success', 'Note deleted.');
    }
}
