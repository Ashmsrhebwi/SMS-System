<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\GlobalBlacklist;
use App\Services\ActivityLogger;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class BlacklistController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', GlobalBlacklist::class);

        $search = $request->get('search');
        $query  = GlobalBlacklist::query();

        if ($search) {
            $query->where('phone', 'like', '%' . $search . '%');
        }

        $entries = $query->latest()->paginate(50)->withQueryString();

        return view('blacklist.index', compact('entries', 'search'));
    }

    public function destroy(GlobalBlacklist $blacklist)
    {
        $this->authorize('delete', $blacklist);

        AuditLogger::log('remove_from_blacklist', null, ['phone' => $blacklist->phone]);

        $contact = Contact::where('phone', $blacklist->phone)->first();
        if ($contact) {
            ActivityLogger::blacklistRemoved($contact);
        }

        $blacklist->delete();
        return back()->with('success', 'Number removed from blacklist.');
    }
}
