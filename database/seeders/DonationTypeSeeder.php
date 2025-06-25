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
        DonationType::create(['name' => 'Niyaz e Hussain']);
        DonationType::create(['name' => 'Zabihat']);
    }
}
