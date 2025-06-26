<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectorReportController extends Controller
{
    public function show(Request $request, $itsId)
    {
        $query = Donation::join('collector_sessions', 'donations.collector_session_id', '=', 'collector_sessions.id')
            ->join('currencies', 'donations.currency_id', '=', 'currencies.id')
            ->where('collector_sessions.its_id', $itsId);

        if ($request->has('event_id')) {
            $query->where('collector_sessions.event_id', $request->input('event_id'));
        }

        $collections = $query->groupBy('donations.currency_id', 'currencies.name', 'currencies.code')
            ->select(
                'currencies.name as currency_name',
                'currencies.code as currency_code',
                DB::raw('SUM(donations.amount) as total_amount')
            )
            ->get();

        return response()->json([
            'its_id' => $itsId,
            'collections' => $collections,
        ]);
    }
}
