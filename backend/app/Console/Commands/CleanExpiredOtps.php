<?php

namespace App\Console\Commands;

use App\Models\LoginOtp;
use Illuminate\Console\Command;

class CleanExpiredOtps extends Command
{
    protected $signature = 'auth:clean-otps {--days=7 : Delete OTP records older than this many days}';

    protected $description = 'Prune expired and used OTP records from login_otps table';

    public function handle(): int
    {
        $cutoff  = now()->subDays((int) $this->option('days'));
        $deleted = LoginOtp::where('expires_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} expired OTP record(s) older than {$this->option('days')} day(s).");

        return self::SUCCESS;
    }
}
