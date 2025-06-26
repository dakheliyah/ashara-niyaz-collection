<?php

namespace Database\Seeders;

use App\Models\DonationType;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DonationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Using updateOrCreate to ensure idempotency and set the tracking flag
        DonationType::updateOrCreate(
            ['name' => 'Niyaz e Hussain'],
            ['tracks_count' => false]
        );

        DonationType::updateOrCreate(
            ['name' => 'Zabihat'],
            ['tracks_count' => true]
        );
    }
}
