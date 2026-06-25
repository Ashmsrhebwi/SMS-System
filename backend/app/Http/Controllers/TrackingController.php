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
        $scheme = $parsed['scheme'] ?? '';
        $host   = $parsed['host'] ?? '';

        if (!in_array($scheme, ['http', 'https'], true)) {
            abort(400, 'Invalid redirect target.');
        }

        // Prevent SSRF — block redirects to private/reserved IP ranges and localhost
        if ($this->isPrivateOrLoopback($host)) {
            abort(400, 'Redirect to internal addresses is not permitted.');
        }

        return redirect()->away($click->target_url, 302);
    }

    private function isPrivateOrLoopback(string $host): bool
    {
        if ($host === '') {
            return true;
        }

        // Explicit loopback/hostname checks (covers IPv6 loopback aliases too)
        if (in_array(strtolower($host), ['localhost', 'ip6-localhost', 'ip6-loopback', '0.0.0.0'], true)) {
            return true;
        }

        // If it's already a raw IP, validate it directly
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ip = $host;
        } else {
            // Resolve hostname to IP; gethostbyname() returns the original string on failure
            $resolved = gethostbyname($host);
            if ($resolved === $host) {
                // Could not resolve — block to be safe (fail closed, not fail open)
                return true;
            }
            $ip = $resolved;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            // Not a valid IP after resolution — block
            return true;
        }

        // Block private ranges (10.x, 172.16–31.x, 192.168.x),
        // loopback (127.x), link-local (169.254.x), and reserved ranges
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
