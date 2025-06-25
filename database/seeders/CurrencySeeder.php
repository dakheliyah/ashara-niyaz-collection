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
        Currency::create(['code' => 'LKR', 'symbol' => 'Rs', 'name' => 'Sri Lankan Rupee']);
        Currency::create(['code' => 'INR', 'symbol' => '₹', 'name' => 'Indian Rupee']);
        Currency::create(['code' => 'USD', 'symbol' => '$', 'name' => 'United States Dollar']);
    }
}
