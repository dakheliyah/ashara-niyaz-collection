<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Admin;
use App\Models\CollectorSession;
use App\Models\Donation;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $eventId = $request->get('event_id');
            
            // Base queries - filter by event if provided
            $sessionQuery = CollectorSession::query();
            $donationQuery = Donation::query();
            
            if ($eventId) {
                $sessionQuery->where('event_id', $eventId);
                $donationQuery->whereHas('collectorSession', function($query) use ($eventId) {
                    $query->where('event_id', $eventId);
                });
            }
            
            $totalSessions = $sessionQuery->count();
            $totalDonations = $donationQuery->count();
            $activeSessions = $sessionQuery->where('status', 'active')->count();

            // Total collected by currency
            $totalCollectedQuery = Donation::join('collector_sessions', 'donations.collector_session_id', '=', 'collector_sessions.id')
                ->join('currencies', 'donations.currency_id', '=', 'currencies.id');
            
            if ($eventId) {
                $totalCollectedQuery->where('collector_sessions.event_id', $eventId);
            }
            
            $totalCollectedByCurrency = $totalCollectedQuery
                ->select('currencies.code', 'currencies.symbol', DB::raw('SUM(donations.amount) as total_amount'))
                ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
                ->get();

            // Category breakdown
            $categoryQuery = Donation::join('collector_sessions', 'donations.collector_session_id', '=', 'collector_sessions.id')
                ->join('donation_types', 'donations.donation_type_id', '=', 'donation_types.id')
                ->join('currencies', 'donations.currency_id', '=', 'currencies.id');
            
            if ($eventId) {
                $categoryQuery->where('collector_sessions.event_id', $eventId);
            }
            
            $categoryBreakdown = $categoryQuery
                ->select(
                    'donation_types.name as category',
                    'currencies.code as currency',
                    'currencies.symbol as currency_symbol',
                    DB::raw('SUM(donations.amount) as total_amount'),
                    DB::raw('COUNT(donations.id) as donation_count')
                )
                ->groupBy('donation_types.id', 'donation_types.name', 'currencies.id', 'currencies.code', 'currencies.symbol')
                ->orderBy('donation_types.name')
                ->orderBy('currencies.code')
                ->get();

            // Collector breakdown
            $collectorQuery = Donation::join('collector_sessions', 'donations.collector_session_id', '=', 'collector_sessions.id')
                ->join('admins', 'collector_sessions.its_id', '=', 'admins.its_id')
                ->join('currencies', 'donations.currency_id', '=', 'currencies.id')
                ->leftJoin('mumineen', 'admins.its_id', '=', 'mumineen.its_id');
            
            if ($eventId) {
                $collectorQuery->where('collector_sessions.event_id', $eventId);
            }
            
            $collectorBreakdown = $collectorQuery
                ->select(
                    'admins.its_id as collector_its_id',
                    'mumineen.fullname as collector_name',
                    'currencies.code as currency',
                    'currencies.symbol as currency_symbol',
                    DB::raw('SUM(donations.amount) as total_amount'),
                    DB::raw('COUNT(donations.id) as donation_count'),
                    DB::raw('COUNT(DISTINCT collector_sessions.id) as session_count')
                )
                ->groupBy('admins.its_id', 'mumineen.fullname', 'currencies.id', 'currencies.code', 'currencies.symbol')
                ->orderBy('mumineen.fullname')
                ->orderBy('currencies.code')
                ->get();

            // Get all admins for reference
            $admins = Admin::with('role')->get();
            
            // Get current active event if no specific event requested
            $currentEvent = null;
            if (!$eventId) {
                $currentEvent = Event::where('is_active', true)->first();
            } else {
                $currentEvent = Event::find($eventId);
            }

            return response()->json([
                'event' => $currentEvent,
                'total_sessions' => $totalSessions,
                'total_donations' => $totalDonations,
                'active_sessions' => $activeSessions,
                'total_collected_by_currency' => $totalCollectedByCurrency,
                'category_breakdown' => $categoryBreakdown,
                'collector_breakdown' => $collectorBreakdown,
                'admins' => $admins,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load dashboard data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent donations for dashboard activity feed
     */
    public function recentDonations()
    {
        $recentDonations = Donation::with(['donationType', 'currency'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($recentDonations);
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
