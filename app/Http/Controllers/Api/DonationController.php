<?php

namespace App\Http\Controllers\Api;

use App\Models\CollectorSession;
use App\Models\Donation;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->attributes->get('admin');
        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }
        $collectorItsId = $user->its_id;

        // Find the collector's active session
        $session = CollectorSession::where('its_id', $collectorItsId)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            return response()->json(['message' => 'No active session found. Please start a session first.'], 403);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'donor_its_id' => 'required|string|max:8',
            'donation_type_id' => 'required|exists:donation_types,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create the donation
        $donation = Donation::create([
            'collector_session_id' => $session->id,
            'donor_its_id' => $request->input('donor_its_id'),
            'donation_type_id' => $request->input('donation_type_id'),
            'currency_id' => $request->input('currency_id'),
            'amount' => $request->input('amount'),
            'donated_at' => now(),
        ]);

        return response()->json($donation, 201);
    }
}
