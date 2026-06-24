<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Services\CountryDetectorService;
use Illuminate\Console\Command;

class BackfillContactCountry extends Command
{
    protected $signature   = 'contacts:backfill-country {--chunk=500}';
    protected $description = 'Detect and store country for existing contacts without a stored country value';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $total = Contact::whereNull('country')->orWhere('country', '')->count();

        if ($total === 0) {
            $this->info('All contacts already have a country stored.');
            return 0;
        }

        $this->info("Backfilling country for {$total} contacts…");
        $bar  = $this->output->createProgressBar($total);
        $done = 0;

        Contact::whereNull('country')
            ->orWhere('country', '')
            ->select(['id', 'phone'])
            ->chunk($chunk, function ($contacts) use ($bar, &$done) {
                foreach ($contacts as $contact) {
                    Contact::where('id', $contact->id)->update([
                        'country' => CountryDetectorService::detect($contact->phone),
                    ]);
                    $bar->advance();
                    $done++;
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Done. Updated {$done} contacts.");
        return 0;
    }
}
