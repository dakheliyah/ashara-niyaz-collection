<?php

namespace App\Http\Controllers\Api;

use App\Models\CollectorSession;
use App\Models\Donation;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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
            'quantity' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create the donation
        $donation = new Donation();
        $donation->collector_session_id = $session->id;
        $donation->donor_its_id = $request->input('donor_its_id');
        $donation->donation_type_id = $request->input('donation_type_id');
        $donation->currency_id = $request->input('currency_id');
        $donation->amount = $request->input('amount');
        $donation->quantity = $request->input('quantity', 1);
        $donation->donated_at = now(); // Corrected column name
        // The 'collected_by' info is correctly stored in the collector_sessions table.
        $donation->save(); // This will trigger the 'creating' event and generate the UUID

        return response()->json($donation, 201);
    }

    /**
     * Generate a PDF receipt for a given donation using its UUID.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function generateReceiptByUuid($uuid)
    {
        // Find the donation by its UUID or fail
        $donation = \App\Models\Donation::where('uuid', $uuid)->firstOrFail();

        // Eager load relationships to avoid N+1 issues in the view
        $donation->load('donationType', 'currency', 'donor');

        // Load the PDF view with the donation data
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt', compact('donation'));

        // Create a descriptive filename
        $filename = 'donation-receipt-' . $donation->uuid . '.pdf';

        // Stream the PDF to the browser
        return $pdf->stream($filename);
    }
}
