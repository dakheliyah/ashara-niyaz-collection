<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\CollectorSession;
use App\Models\Donation;
use App\Models\DonationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventDashboardController extends Controller
{
    /**
     * Get dashboard data for a specific event
     */
    public function show($eventId)
    {
        try {
            $event = Event::findOrFail($eventId);
            
            // Event-specific metrics
            $totalSessions = CollectorSession::where('event_id', $eventId)->count();
            $totalDonations = Donation::whereHas('collectorSession', function($query) use ($eventId) {
                $query->where('event_id', $eventId);
            })->count();
            $activeSessions = CollectorSession::where('event_id', $eventId)
                ->where('status', 'active')->count();

            // Zabihat count for this event
            $zabihatTypeId = DonationType::where('name', 'Zabihat')->value('id');
            $totalZabihat = 0;
            if ($zabihatTypeId) {
                $totalZabihat = Donation::whereHas('collectorSession', function($query) use ($eventId) {
                    $query->where('event_id', $eventId);
                })
                ->where('donation_type_id', $zabihatTypeId)
                ->sum('quantity');
            }
            
            // Total collected by currency for this event
            $currencyTotals = Donation::join('collector_sessions', 'donations.collector_session_id', '=', 'collector_sessions.id')
                ->join('currencies', 'donations.currency_id', '=', 'currencies.id')
                ->where('collector_sessions.event_id', $eventId)
                ->select(
                    'currencies.code as currency',
                    'currencies.symbol as currency_symbol',
                    DB::raw('SUM(donations.amount) as total_amount'),
                    DB::raw('COUNT(donations.id) as donation_count')
                )
                ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
                ->get();

            // Category breakdown for this event
            $categoryBreakdown = Donation::join('collector_sessions', 'donations.collector_session_id', '=', 'collector_sessions.id')
                ->join('donation_types', 'donations.donation_type_id', '=', 'donation_types.id')
                ->join('currencies', 'donations.currency_id', '=', 'currencies.id')
                ->where('collector_sessions.event_id', $eventId)
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

            // Collector breakdown for this event
            $collectorBreakdown = Donation::join('collector_sessions', 'donations.collector_session_id', '=', 'collector_sessions.id')
                ->join('admins', 'collector_sessions.its_id', '=', 'admins.its_id')
                ->join('currencies', 'donations.currency_id', '=', 'currencies.id')
                ->leftJoin('mumineen', 'admins.its_id', '=', 'mumineen.its_id')
                ->where('collector_sessions.event_id', $eventId)
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

            // Collector sessions for this event with reconciliation status
            $collectorSessions = CollectorSession::with(['collector.role', 'event'])
                ->where('event_id', $eventId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'event' => $event,
                'total_sessions' => $totalSessions,
                'total_donations' => $totalDonations,
                'active_sessions' => $activeSessions,
                'total_zabihat' => (int)$totalZabihat,
                'currency_totals' => $currencyTotals,
                'category_breakdown' => $categoryBreakdown,
                'collector_breakdown' => $collectorBreakdown,
                'collector_sessions' => $collectorSessions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load event dashboard data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get collector breakdown for a specific session
     */
    public function sessionBreakdown($sessionId)
    {
        try {
            $session = CollectorSession::with(['collector', 'event'])->findOrFail($sessionId);
            
            // Donations for this specific session
            $donations = Donation::with(['donationType', 'currency'])
                ->where('collector_session_id', $sessionId)
                ->orderBy('donated_at', 'desc')
                ->get();

            // Summary by currency for this session
            $currencyBreakdown = Donation::join('currencies', 'donations.currency_id', '=', 'currencies.id')
                ->where('collector_session_id', $sessionId)
                ->select('currencies.code', 'currencies.symbol', DB::raw('SUM(donations.amount) as total_amount'), DB::raw('COUNT(donations.id) as donation_count'))
                ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
                ->get();

            // Summary by donation type for this session
            $typeBreakdown = Donation::join('donation_types', 'donations.donation_type_id', '=', 'donation_types.id')
                ->join('currencies', 'donations.currency_id', '=', 'currencies.id')
                ->where('collector_session_id', $sessionId)
                ->select(
                    'donation_types.name as category',
                    'currencies.code as currency',
                    'currencies.symbol as currency_symbol',
                    DB::raw('SUM(donations.amount) as total_amount'),
                    DB::raw('COUNT(donations.id) as donation_count')
                )
                ->groupBy('donation_types.id', 'donation_types.name', 'currencies.id', 'currencies.code', 'currencies.symbol')
                ->get();

            return response()->json([
                'session' => $session,
                'donations' => $donations,
                'currency_breakdown' => $currencyBreakdown,
                'type_breakdown' => $typeBreakdown,
                'total_donations' => $donations->count(),
                'total_amount' => $donations->sum('amount'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load session breakdown',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a collector session as reconciled
     */
    public function reconcileSession(Request $request, $sessionId)
    {
        try {
            $session = CollectorSession::findOrFail($sessionId);
            
            // Only allow reconciliation of ended sessions
            if ($session->status !== 'ended') {
                return response()->json([
                    'error' => 'Can only reconcile ended sessions'
                ], 400);
            }

            $session->update([
                'reconciliation_status' => 'reconciled',
                'reconciled_by' => $request->user()->its_id ?? 'admin',
                'reconciled_at' => now(),
            ]);

            return response()->json([
                'message' => 'Session reconciled successfully',
                'session' => $session->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reconcile session',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
