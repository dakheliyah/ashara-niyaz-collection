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
        $collectorItsId = $request->attributes->get('its_id');

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
            'whatsapp_number' => 'nullable|string|max:15',
            'donation_type_id' => 'required|exists:donation_types,id',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create the donation
        $donation = Donation::create([
            'collector_session_id' => $session->id,
            'donor_its_id' => $request->input('donor_its_id'),
            'whatsapp_number' => $request->input('whatsapp_number'),
            'donation_type_id' => $request->input('donation_type_id'),
            'currency_id' => $request->input('currency_id'),
            'amount' => $request->input('amount'),
            'donated_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        return response()->json($donation, 201);
    }
}
