<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Currency::query();

        if (!request()->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        return $query->orderBy('code')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:3|alpha|unique:currencies,code',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
        ]);

        $currency = Currency::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'symbol' => $validated['symbol'],
            'is_active' => true,
        ]);

        return response()->json($currency, 201);
    }

    public function activate(Currency $currency)
    {
        $currency->update(['is_active' => true]);

        return response()->json($currency->fresh());
    }

    public function deactivate(Currency $currency)
    {
        $currency->update(['is_active' => false]);

        return response()->json($currency->fresh());
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
