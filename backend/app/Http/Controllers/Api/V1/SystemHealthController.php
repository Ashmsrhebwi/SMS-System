<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemHealthController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return response()->json([
            'queue'    => $this->queueHealth(),
            'database' => $this->databaseHealth(),
            'twilio'   => $this->twilioHealth(),
            'system'   => $this->systemInfo(),
            'platform' => $this->platformStats(),
        ]);
    }

    private function queueHealth(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();
            $recent  = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHour())
                ->count();

            return [
                'status'           => $failed > 20 ? 'critical' : ($failed > 5 ? 'warning' : 'healthy'),
                'pending_jobs'     => $pending,
                'failed_jobs'      => $failed,
                'failed_last_hour' => $recent,
                'recent_failures'  => DB::table('failed_jobs')
                    ->orderByDesc('failed_at')
                    ->limit(5)
                    ->get(['id', 'queue', 'payload', 'exception', 'failed_at'])
                    ->map(fn($j) => [
                        'id'        => $j->id,
                        'queue'     => $j->queue,
                        'job'       => $this->extractJobName($j->payload),
                        'error'     => substr($j->exception, 0, 200),
                        'failed_at' => $j->failed_at,
                    ]),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'unknown', 'error' => $e->getMessage()];
        }
    }

    private function databaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $ms = round((microtime(true) - $start) * 1000, 2);

            return [
                'status'         => 'healthy',
                'response_ms'    => $ms,
                'connection'     => config('database.default'),
                'total_messages' => Message::count(),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function twilioHealth(): array
    {
        $sid   = config('services.twilio.account_sid');
        $token = config('services.twilio.auth_token');
        $msSid = config('services.twilio.messaging_service_sid');

        $configured = !empty($sid) && !empty($token) && !empty($msSid);

        $recentErrors = Message::where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $recentSent = Message::whereIn('status', ['delivered', 'sent', 'queued'])
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return [
            'status'             => !$configured ? 'not_configured' : ($recentErrors > 20 ? 'warning' : 'healthy'),
            'configured'         => $configured,
            'account_sid'        => $sid ? substr($sid, 0, 6) . '...' : null,
            'messaging_service'  => $msSid ? substr($msSid, 0, 6) . '...' : null,
            'failed_last_hour'   => $recentErrors,
            'sent_last_24h'      => $recentSent,
        ];
    }

    private function systemInfo(): array
    {
        return [
            'php_version'     => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment'     => app()->environment(),
            'debug_mode'      => config('app.debug'),
            'memory_used_mb'  => round(memory_get_usage(true) / 1024 / 1024, 1),
            'memory_limit_mb' => (int) ini_get('memory_limit'),
            'timezone'        => config('app.timezone'),
            'cache_driver'    => config('cache.default'),
            'queue_driver'    => config('queue.default'),
            'mail_driver'     => config('mail.default'),
        ];
    }

    private function platformStats(): array
    {
        $lastSuccessfulCampaign = Campaign::where('status', 'completed')
            ->latest('updated_at')
            ->first(['id', 'name', 'total_recipients', 'updated_at']);

        $lastFailedCampaign = Campaign::where('status', 'cancelled')
            ->latest('updated_at')
            ->first(['id', 'name', 'updated_at']);

        $activeUsers = Cache::remember('health:active_users', 60, fn() =>
            DB::table('personal_access_tokens')
                ->where('last_used_at', '>=', now()->subDay())
                ->distinct('tokenable_id')
                ->count('tokenable_id')
        );

        return [
            'active_users_24h'          => $activeUsers,
            'total_users'               => User::count(),
            'last_successful_campaign'  => $lastSuccessfulCampaign,
            'last_failed_campaign'      => $lastFailedCampaign,
            'messages_today'            => Message::whereDate('created_at', today())->count(),
            'failed_messages_today'     => Message::whereDate('created_at', today())
                ->whereIn('status', ['failed', 'undelivered'])->count(),
            'cost_today'                => round((float) Message::whereDate('created_at', today())->sum('cost'), 4),
        ];
    }

    private function extractJobName(string $payload): string
    {
        $data = json_decode($payload, true);
        return class_basename($data['displayName'] ?? $data['job'] ?? 'Unknown');
    }
}
