<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Exports\DetailedReportExport;
use App\Exports\SummaryReportExport;
use App\Models\CollectorSession;
use App\Models\Donation;
use App\Models\DonationType;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Models\Currency;

class ReportController extends Controller
{
    public function getSummaryReport(Request $request)
    {
        list($summary, $currencyCodes) = $this->getAggregatedSummaryData($request);

        return response()->json([
            'summary' => $summary,
            'currencies' => $currencyCodes,
        ]);
    }

    public function getDetailedReport(Request $request)
    {
        $query = Donation::with(['collectorSession.collector.mumineen', 'collectorSession.event', 'donor', 'donationType', 'currency'])
            ->orderBy('donated_at', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->whereBetween('donated_at', [$startDate, $endDate]);
        }

        if ($request->filled('collector_its')) {
            $query->whereHas('collectorSession.collector', function ($q) use ($request) {
                $q->where('its_id', $request->input('collector_its'));
            });
        }

        if ($request->filled('event_id')) {
            $query->whereHas('collectorSession', function ($q) use ($request) {
                $q->where('event_id', $request->input('event_id'));
            });
        }

        $detailed = $query->paginate(15);
        
        // Modify the collection to replace the fullname
        $detailed->getCollection()->transform(function($donation) {
            if ($donation->collectorSession && $donation->collectorSession->collector) {
                // To avoid breaking the frontend, we just overwrite the fullname property
                $donation->collectorSession->collector->fullname = $donation->collectorSession->collector->mumineen->fullname 
                    ?? $donation->collectorSession->collector->its_id;
            }
            return $donation;
        });

        return response()->json($detailed);
    }

    public function exportSummaryReport(Request $request)
    {
        list($summary, $currencyCodes) = $this->getAggregatedSummaryData($request);

        return Excel::download(new SummaryReportExport($summary, $currencyCodes), 'collector-summary-report.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    public function getZabihatCount(Request $request)
    {
        $zabihatType = DonationType::where('name', 'Zabihat')->first();

        if (!$zabihatType) {
            return response()->json(['total_zabihat' => 0]);
        }

        $query = Donation::where('donation_type_id', $zabihatType->id);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->whereBetween('donated_at', [$startDate, $endDate]);
        }

        $totalZabihat = $query->sum('quantity');

        return response()->json(['total_zabihat' => (int)$totalZabihat]);
    }

    private function getAggregatedSummaryData(Request $request)
    {
        $query = CollectorSession::with(['collector.mumineen', 'donations.currency', 'event'])
            ->withCount('donations');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->whereBetween('started_at', [$startDate, $endDate]);
        }

        if ($request->filled('collector_its')) {
            $query->whereHas('collector', function ($q) use ($request) {
                $q->where('its_id', $request->input('collector_its'));
            });
        }

        // I am assuming collector_sessions table has an event_id column.
        // If not, this will need to be changed to use whereHas('donations', ...)
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        $sessions = $query->get();

        $currencyCodes = Currency::all()->pluck('code')->all();

        $summary = $sessions->map(function ($session) use ($currencyCodes) {
            $collector = $session->collector;

            $reportRow = [
                'collector_name' => optional($collector->mumineen)->fullname ?? $collector->its_id,
                'collector_its' => $collector->its_id,
                'session_id' => $session->id,
                'session_start' => $session->started_at,
                'total_donations' => $session->donations_count,
                'event_id' => optional($session->event)->id,
                'event_name' => optional($session->event)->name,
            ];

            // Initialize all currency columns to 0
            foreach ($currencyCodes as $code) {
                $reportRow[$code] = 0;
            }

            // Sum donations for this session by currency
            $donationsByCurrency = $session->donations->groupBy('currency.code');
            foreach ($donationsByCurrency as $code => $donations) {
                if (in_array($code, $currencyCodes)) {
                    $reportRow[$code] = $donations->sum('amount');
                }
            }

            return $reportRow;
        })->values();

        return [$summary, $currencyCodes];
    }

    public function exportDetailedReport(Request $request)
    {
        $query = Donation::with(['collectorSession.collector.mumineen', 'collectorSession.event', 'donor', 'donationType', 'currency'])
            ->orderBy('donated_at', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->whereBetween('donated_at', [$startDate, $endDate]);
        }

        if ($request->filled('collector_its')) {
            $query->whereHas('collectorSession.collector', function ($q) use ($request) {
                $q->where('its_id', $request->input('collector_its'));
            });
        }

        $detailed = $query->get();

        // Modify the collection to replace the fullname for the export
        $detailed->transform(function($donation) {
            if ($donation->collectorSession && $donation->collectorSession->collector) {
                $donation->collectorSession->collector->fullname = $donation->collectorSession->collector->mumineen->fullname 
                    ?? $donation->collectorSession->collector->its_id;
            }
            return $donation;
        });

        return Excel::download(new DetailedReportExport($detailed), 'collector-detailed-report.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}
