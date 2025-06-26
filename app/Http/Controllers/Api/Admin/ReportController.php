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
        $query = Donation::with(['collectorSession.collector.mumineen', 'donor', 'donationType', 'currency'])
            ->orderBy('donated_at', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('donated_at', [$request->input('start_date'), $request->input('end_date')]);
        }

        if ($request->filled('collector_its')) {
            $query->whereHas('collectorSession.collector', function ($q) use ($request) {
                $q->where('its_id', $request->input('collector_its'));
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
            $query->whereBetween('donated_at', [$request->input('start_date'), $request->input('end_date')]);
        }

        $totalZabihat = $query->sum('quantity');

        return response()->json(['total_zabihat' => (int)$totalZabihat]);
    }

    private function getAggregatedSummaryData(Request $request)
    {
        $query = CollectorSession::with(['collector.mumineen', 'donations.currency'])
            ->withCount('donations');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('started_at', [$request->input('start_date'), $request->input('end_date')]);
        }

        if ($request->filled('collector_its')) {
            $query->whereHas('collector', function ($q) use ($request) {
                $q->where('its_id', $request->input('collector_its'));
            });
        }

        $sessions = $query->get();

        $sessionsByCollector = $sessions->groupBy('collector.its_id');

        $currencyCodes = Currency::all()->pluck('code')->all();

        $summary = $sessionsByCollector->map(function ($collectorSessions, $itsId) use ($currencyCodes) {
            $firstSession = $collectorSessions->first();
            if (!$firstSession) return null;

            $collector = $firstSession->collector;

            $reportRow = [
                'collector_name' => $collector->mumineen->fullname ?? $collector->its_id,
                'collector_its' => $collector->its_id,
                'total_sessions' => $collectorSessions->count(),
                'total_donations' => $collectorSessions->sum('donations_count'),
            ];

            foreach ($currencyCodes as $code) {
                $reportRow[$code] = 0;
            }

            foreach ($collectorSessions as $session) {
                $donationsByCurrency = $session->donations->groupBy('currency.code');
                foreach ($donationsByCurrency as $code => $donations) {
                    if (isset($reportRow[$code])) {
                        $reportRow[$code] += $donations->sum('amount');
                    }
                }
            }

            return $reportRow;
        })->filter()->values();

        return [$summary, $currencyCodes];
    }

    public function exportDetailedReport(Request $request)
    {
        $query = Donation::with(['collectorSession.collector.mumineen', 'donor', 'donationType', 'currency'])
            ->orderBy('donated_at', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('donated_at', [$request->input('start_date'), $request->input('end_date')]);
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
