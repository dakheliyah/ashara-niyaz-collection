<?php

namespace App\Http\Controllers\Api\Donor;

use App\Models\Donation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->attributes->get('admin');
        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }
        $donorItsId = $user->its_id;

        // Retrieve donations with their related type and currency
        $donations = Donation::with(['donationType', 'currency'])
            ->where('donor_its_id', $donorItsId)
            ->orderBy('donated_at', 'desc')
            ->get();

        return response()->json($donations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
