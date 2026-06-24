<?php

namespace App\Http\Controllers;

use App\Models\Click;
use App\Services\ActivityLogger;

class TrackingController extends Controller
{
    public function click(string $token)
    {
        $click = Click::where('token', $token)->first();

        if (!$click) {
            abort(404);
        }

        $click->increment('click_count');

        if ($click->click_count === 1 || $click->first_clicked_at === null) {
            $click->update(['first_clicked_at' => now()]);
        }

        // Log activity (load message with contact and campaign)
        $message = $click->message()->with(['contact', 'campaign'])->first();
        if ($message) {
            ActivityLogger::linkClicked($message);
        }

        $parsed = parse_url($click->target_url);
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            abort(400, 'Invalid redirect target.');
        }

        return redirect($click->target_url);
    }
}
