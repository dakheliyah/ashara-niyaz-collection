<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectorReportController extends Controller
{
    /**
     * Get a detailed, paginated report of all donations, filterable by date.
     */
    public function getDetailedReport(Request $request)
    {
        $query = Donation::with(['currency', 'donationType', 'collectorSession.event'])
            ->join('mumineen as donor', 'donations.donor_its_id', '=', 'donor.its_id')
            ->join('collector_sessions', 'donations.collector_session_id', '=', 'collector_sessions.id')
            ->join('mumineen as collector', 'collector_sessions.its_id', '=', 'collector.its_id')
            ->select(
                'donations.created_at as date',
                'donations.id as donation_id',
                'donations.donor_its_id',
                'donor.fullname as donor_name',
                'donor.jamaat as donor_jamaat',
                'donations.amount',
                'donations.currency_id',
                'donations.donation_type_id',
                'collector_sessions.its_id as collector_its_id',
                'collector.fullname as collector_name'
            );

        // Optional ITS ID filtering
        if ($request->filled('its_id')) {
            $query->where('collector_sessions.its_id', $request->input('its_id'));
        }

        // Optional date filtering
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('donations.created_at', [$request->start_date, $request->end_date]);
        }

        $donations = $query->orderBy('donations.created_at', 'desc')->paginate(25);

        return response()->json($donations);
    }

    /**
     * Get a summary of collections, grouped by collector, session, and currency.
     */
    public function getSummaryReport(Request $request)
    {
        $query = DB::table('donations')
            ->join('collector_sessions', 'donations.collector_session_id', '=', 'collector_sessions.id')
            ->join('currencies', 'donations.currency_id', '=', 'currencies.id')
            ->join('mumineen as collector', 'collector_sessions.its_id', '=', 'collector.its_id');

        // Optional ITS ID filtering
        if ($request->filled('its_id')) {
            $query->where('collector_sessions.its_id', $request->input('its_id'));
        }

        // Optional date filtering
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('donations.created_at', [$request->start_date, $request->end_date]);
        }

        $query->select(
                'collector_sessions.its_id as collector_its',
                'collector.fullname as collector_name',
                'donations.collector_session_id',
                'collector_sessions.started_at',
                'collector_sessions.ended_at',
                'currencies.code as currency_code',
                DB::raw('SUM(donations.amount) as total_amount')
            )
            ->groupBy(
                'collector_sessions.its_id',
                'collector.fullname',
                'donations.collector_session_id',
                'collector_sessions.started_at',
                'collector_sessions.ended_at',
                'currencies.code'
            )
            ->orderBy('collector.fullname')->orderBy('collector_sessions.started_at', 'desc');

        $summary = $query->get();

        // Further process the data to group by collector and then by session
        $report = $summary->groupBy('collector_its')->map(function ($collectorData) {
            return [
                'collector_name' => $collectorData->first()->collector_name,
                'sessions' => $collectorData->groupBy('collector_session_id')->map(function ($sessionData, $sessionId) {
                    return [
                        'session_id' => $sessionId,
                        'session_start' => $sessionData->first()->started_at,
                        'session_end' => $sessionData->first()->ended_at,
                        'collections' => $sessionData->map(function ($currencyData) {
                            return [
                                'currency' => $currencyData->currency_code,
                                'total' => $currencyData->total_amount,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return response()->json($report);
    }
}

