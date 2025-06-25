<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the roles
        $adminRole = Role::where('name', 'admin')->first();
        $collectorRole = Role::where('name', 'collector')->first();

        if ($adminRole) {
            // Create or update the admin user
            Admin::updateOrCreate(
                ['its_id' => 30361114], // Find admin by this ITS ID
                [
                    'role_id' => $adminRole->id,
                    'token' => urldecode('geFp5FFAagw7YvRYNDiREj%2BC5wY1RjQWm9K%2FDxxTTPo%3D'), // Admin token (decoded)
                    'created_by' => 'system',
                ]
            );
        }

        if ($collectorRole) {
            // Create or update the collector user
            Admin::updateOrCreate(
                ['its_id' => 20324216], // Find collector by this ITS ID
                [
                    'role_id' => $collectorRole->id,
                    'token' => urldecode('PMVU4zSbtB%2FMFMCD2p%2BPwQ8rBUkc6TYQi1mNaTyoFpE%3D'), // Collector token (decoded)
                    'created_by' => 'system',
                ]
            );
        }

        echo "Created/Updated admin and collector users with tokens\n";
    }
}
