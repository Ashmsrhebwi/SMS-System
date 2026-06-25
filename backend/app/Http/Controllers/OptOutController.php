<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\GlobalBlacklist;
use App\Models\OptOut;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OptOutController extends Controller
{
    /**
     * Show the opt-out confirmation form.
     * Requires a valid HMAC signature tied to the campaign + contact IDs.
     */
    public function form(Request $request)
    {
        $campaignId = $request->query('campaign');
        $contactId  = $request->query('contact');
        $sig        = $request->query('sig');

        if (!$this->isValidSignature($campaignId, $contactId, $sig)) {
            abort(403, 'This unsubscribe link is invalid or has expired.');
        }

        $campaign = Campaign::find($campaignId);
        $contact  = Contact::find($contactId);

        // Pass the raw numeric IDs (not derived from model objects) so the hidden form
        // fields are always populated even when the campaign has since been deleted.
        return view('optout.form', [
            'campaign'   => $campaign,
            'contact'    => $contact,
            'sig'        => $sig,
            'campaignId' => $campaignId,
            'contactId'  => $contactId,
        ]);
    }

    /**
     * Process the opt-out.
     * Re-validates the HMAC signature and uses the phone number from the DB,
     * never from user-submitted form data.
     */
    public function process(Request $request)
    {
        $data = $request->validate([
            'campaign_id' => 'required|string',
            'contact_id'  => 'required|string',
            'sig'         => 'required|string',
            'reason'      => 'nullable|string|max:500',
        ]);

        // Re-validate the signature on every submission — prevents forged requests
        if (!$this->isValidSignature($data['campaign_id'], $data['contact_id'], $data['sig'])) {
            abort(403, 'This unsubscribe link is invalid or has expired.');
        }

        // Phone comes from the database, NEVER from form input.
        // withTrashed: allow opt-out even if contact was soft-deleted after the SMS was sent.
        $contact = Contact::withTrashed()->find($data['contact_id']);
        if (!$contact) {
            return view('optout.confirmed');
        }

        $phone  = $contact->phone;
        $reason = trim($data['reason'] ?? '') ?: 'User requested opt-out via link';

        $contact->update(['opted_in' => false]);
        OptOut::firstOrCreate(['phone' => $phone], ['reason' => $reason]);
        GlobalBlacklist::firstOrCreate(['phone' => $phone], ['reason' => $reason]);
        ActivityLogger::optedOut($contact);

        Log::info('Contact opted out via signed link', [
            'contact_id'  => $contact->id,
            'campaign_id' => $data['campaign_id'],
            'phone'       => substr($phone, 0, 5) . '****',
        ]);

        return view('optout.confirmed');
    }

    /**
     * Verify the HMAC-SHA256 signature for opt-out URLs.
     * The signature is computed over "campaign={id}&contact={id}" using the app key.
     * hash_equals() prevents timing attacks.
     */
    private function isValidSignature(?string $campaignId, ?string $contactId, ?string $sig): bool
    {
        if (!$campaignId || !$contactId || !$sig || !is_numeric($campaignId) || !is_numeric($contactId)) {
            return false;
        }

        $expected = hash_hmac('sha256', "campaign={$campaignId}&contact={$contactId}", config('app.key'));

        return hash_equals($expected, $sig);
    }
}
