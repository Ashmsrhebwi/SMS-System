<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\GlobalBlacklist;
use App\Models\OptOut;
use App\Services\ActivityLogger;
use App\Services\PhoneNormalizerService;
use Illuminate\Http\Request;

class OptOutController extends Controller
{
    public function form(Request $request)
    {
        $campaign = Campaign::find($request->query('campaign'));
        $contact  = Contact::find($request->query('contact'));
        return view('optout.form', compact('campaign', 'contact'));
    }

    public function process(Request $request)
    {
        $data = $request->validate([
            'phone'  => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $normalizer = app(PhoneNormalizerService::class);
        $phone      = $normalizer->normalize($data['phone']) ?? $data['phone'];
        $reason     = $data['reason'] ?? 'User requested opt-out';

        Contact::where('phone', $phone)->update(['opted_in' => false]);

        OptOut::firstOrCreate(['phone' => $phone], ['reason' => $reason]);
        GlobalBlacklist::firstOrCreate(['phone' => $phone], ['reason' => $reason]);

        $contact = Contact::where('phone', $phone)->first();
        if ($contact) {
            ActivityLogger::optedOut($contact);
        }

        return view('optout.confirmed');
    }
}
