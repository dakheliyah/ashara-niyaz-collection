<?php

namespace Database\Seeders;

use App\Models\Currency;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Currency::updateOrCreate(['code' => 'LKR'], ['symbol' => 'Rs', 'name' => 'Sri Lankan Rupee']);
        Currency::updateOrCreate(['code' => 'INR'], ['symbol' => '₹', 'name' => 'Indian Rupee']);
        Currency::updateOrCreate(['code' => 'USD'], ['symbol' => '$', 'name' => 'United States Dollar']);
        Currency::updateOrCreate(['code' => 'EUR'], ['symbol' => '€', 'name' => 'Euro']);
    }
}
