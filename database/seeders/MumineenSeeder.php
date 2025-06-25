<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Mumineen;

class MumineenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mumineen = [
            [
                'its_id' => 12345678,
                'hof_id' => 12345600,
                'fullname' => 'Fatima Hussain Merchant',
                'gender' => 'Female',
                'age' => 28,
                'mobile' => '+91-9876543211',
                'country' => 'India',
                'jamaat' => 'Delhi',
            ],
            [
                'its_id' => 87654321,
                'hof_id' => 87654300,
                'fullname' => 'Mohammed Taher Jamali',
                'gender' => 'Male',
                'age' => 42,
                'mobile' => '+91-9876543212',
                'country' => 'India',
                'jamaat' => 'Pune',
            ],
            [
                'its_id' => 11111111,
                'hof_id' => 11111100,
                'fullname' => 'Zainab Khorakiwala',
                'gender' => 'Female',
                'age' => 31,
                'mobile' => '+91-9876543213',
                'country' => 'India',
                'jamaat' => 'Surat',
            ],
            [
                'its_id' => 99999999,
                'hof_id' => 99999900,
                'fullname' => 'Husain Rangwala',
                'gender' => 'Male',
                'age' => 25,
                'mobile' => '+91-9876543214',
                'country' => 'India',
                'jamaat' => 'Ahmedabad',
            ],
            [
                'its_id' => 55555555,
                'hof_id' => 55555500,
                'fullname' => 'Aisha Patel',
                'gender' => 'Female',
                'age' => 29,
                'mobile' => '+91-9876543215',
                'country' => 'India',
                'jamaat' => 'Bangalore',
            ],
        ];

        foreach ($mumineen as $mumin) {
            // Only create if the ITS ID doesn't already exist
            Mumineen::firstOrCreate(
                ['its_id' => $mumin['its_id']], // Check by ITS ID
                $mumin // Create with these values if not found
            );
        }

        echo "Seeded " . count($mumineen) . " additional mumineen records (skipped existing ones)\n";
    }
}
