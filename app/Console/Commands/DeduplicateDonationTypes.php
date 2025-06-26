<?php

namespace App\Console\Commands;

use App\Models\Donation;
use App\Models\DonationType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateDonationTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:deduplicate-donation-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds and merges duplicate donation types, updating associated donations.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting deduplication of donation types...');

        DB::transaction(function () {
            // Find all donation type names that are duplicated
            $duplicatedNames = DonationType::query()
                ->select('name')
                ->groupBy('name')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('name');

            if ($duplicatedNames->isEmpty()) {
                $this->info('No duplicate donation types found.');
                return;
            }

            $this->info('Found ' . $duplicatedNames->count() . ' duplicated names: ' . $duplicatedNames->implode(', '));

            foreach ($duplicatedNames as $name) {
                $this->line("Processing duplicates for: {$name}");

                // Get all types with the current name, ordered by ID
                $types = DonationType::where('name', $name)->orderBy('id')->get();

                // The first one is the master record we'll keep
                $masterType = $types->first();
                $this->line("  Master record ID: {$masterType->id}");

                // The rest are duplicates to be merged and deleted
                $duplicateIds = $types->slice(1)->pluck('id');
                $this->line("  Duplicate record IDs to merge: " . $duplicateIds->implode(', '));

                if ($duplicateIds->isNotEmpty()) {
                    // Update all donations pointing to duplicate types to point to the master type
                    $affectedDonations = Donation::whereIn('donation_type_id', $duplicateIds)
                        ->update(['donation_type_id' => $masterType->id]);

                    $this->info("  Updated {$affectedDonations} donation records.");

                    // Now, delete the duplicate types
                    $deletedCount = DonationType::whereIn('id', $duplicateIds)->delete();
                    $this->info("  Deleted {$deletedCount} duplicate donation type records.");
                }
            }
        });

        $this->info('Deduplication process completed successfully.');
        return 0;
    }
}
