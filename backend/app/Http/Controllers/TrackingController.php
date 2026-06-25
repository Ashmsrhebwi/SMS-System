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

        // Explicit loopback/hostname checks
        if (in_array(strtolower($host), ['localhost', 'ip6-localhost', 'ip6-loopback'], true)) {
            return true;
        }

        // Resolve to IP (getaddrinfo equivalent via PHP)
        $ip = filter_var($host, FILTER_VALIDATE_IP)
            ? $host
            : gethostbyname($host);

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false; // could not resolve — allow, let HTTP fail naturally
        }

        // Block private ranges (10.x, 172.16–31.x, 192.168.x), loopback (127.x), link-local (169.254.x), reserved
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
