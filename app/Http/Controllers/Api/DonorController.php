<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mumineen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DonorController extends Controller
{
    /**
     * Get donor information by ITS ID from the mumineen table
     */
    public function getDonorByItsId(Request $request, $itsId)
    {
        // Validate ITS ID format
        if (!preg_match('/^\d{8}$/', $itsId)) {
            return response()->json(['error' => 'Invalid ITS ID format'], 400);
        }

        try {
            // Look up the mumin in the mumineen table
            $mumin = Mumineen::where('its_id', $itsId)->first();

            if ($mumin) {
                return response()->json([
                    'its_id' => $mumin->its_id,
                    'fullname' => $mumin->fullname,
                    'mobile' => $mumin->mobile,
                    'gender' => $mumin->gender,
                    'age' => $mumin->age,
                    'country' => $mumin->country,
                    'jamaat' => $mumin->jamaat,
                ]);
            }

            // If donor not found, return basic structure with ITS ID
            return response()->json([
                'its_id' => $itsId,
                'fullname' => null,
                'mobile' => null,
                'gender' => null,
                'age' => null,
                'country' => null,
                'jamaat' => null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error looking up donor: ' . $e->getMessage());
            return response()->json(['error' => 'Database error occurred'], 500);
        }
    }
}
