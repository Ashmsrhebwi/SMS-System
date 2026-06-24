<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Message;
use App\Services\CountryDetectorService;
use Illuminate\Support\Collection;

class CountryAnalyticsController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $contacts = Contact::select('phone')->get();

        $byCountry = [];
        foreach ($contacts as $contact) {
            $country = CountryDetectorService::detect($contact->phone);
            if (!isset($byCountry[$country])) {
                $byCountry[$country] = [
                    'country'       => $country,
                    'contacts'      => 0,
                    'messages_sent' => 0,
                    'delivered'     => 0,
                    'failed'        => 0,
                ];
            }
            $byCountry[$country]['contacts']++;
        }

        // Join with message stats
        $messageCounts = Message::join('contacts', 'messages.contact_id', '=', 'contacts.id')
            ->selectRaw('contacts.phone, messages.status')
            ->whereNotNull('contacts.phone')
            ->get();

        foreach ($messageCounts as $msg) {
            $country = CountryDetectorService::detect($msg->phone);
            if (!isset($byCountry[$country])) {
                $byCountry[$country] = [
                    'country' => $country, 'contacts' => 0,
                    'messages_sent' => 0, 'delivered' => 0, 'failed' => 0,
                ];
            }
            if (in_array($msg->status, ['sent', 'delivered', 'failed', 'undelivered', 'queued'])) {
                $byCountry[$country]['messages_sent']++;
            }
            if ($msg->status === 'delivered') {
                $byCountry[$country]['delivered']++;
            }
            if (in_array($msg->status, ['failed', 'undelivered'])) {
                $byCountry[$country]['failed']++;
            }
        }

        // Compute delivery rate and sort by contacts desc
        $stats = collect(array_values($byCountry))
            ->map(function ($row) {
                $row['delivery_rate'] = $row['messages_sent'] > 0
                    ? round(($row['delivered'] / $row['messages_sent']) * 100, 1)
                    : 0;
                return $row;
            })
            ->sortByDesc('contacts')
            ->values();

        $topCountries = $stats->take(10);

        return view('reports.countries', compact('stats', 'topCountries'));
    }
}
