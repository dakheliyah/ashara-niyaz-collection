<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Exports\DetailedReportExport;
use App\Exports\SummaryReportExport;
use App\Models\CollectorSession;
use App\Models\Donation;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Currency;

class ReportController extends Controller
{
    public function getSummaryReport(Request $request)
    {
        $query = CollectorSession::with(['collector', 'donations.currency'])
            ->withCount('donations');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('started_at', [$request->input('start_date'), $request->input('end_date')]);
        }

        if ($request->filled('collector_its')) {
            $query->whereHas('collector', function ($q) use ($request) {
                $q->where('its_id', $request->input('collector_its'));
            });
        }

        $sessions = $query->orderBy('started_at', 'desc')->get();

        // Get all currency codes to create columns
        $currencyCodes = Currency::all()->pluck('code')->all();

        $summary = $sessions->map(function ($session) use ($currencyCodes) {
            $reportRow = [
                'collector_name' => $session->collector->fullname,
                'collector_its' => $session->collector->its_id,
                'session_id' => $session->id,
                'started_at' => $session->started_at,
                'ended_at' => $session->ended_at,
                'total_donations' => $session->donations_count,
            ];

            // Initialize all currency columns to 0
            foreach ($currencyCodes as $code) {
                $reportRow[$code] = 0;
            }

            // Group donations by currency and sum amounts
            $donationsByCurrency = $session->donations->groupBy('currency.code');

            foreach ($donationsByCurrency as $code => $donations) {
                if (in_array($code, $currencyCodes)) {
                    $reportRow[$code] = $donations->sum('amount');
                }
            }

            return $reportRow;
        });

        return response()->json([
            'summary' => $summary,
            'currencies' => $currencyCodes,
        ]);
    }

    public function getDetailedReport(Request $request)
    {
        $query = Donation::with(['collectorSession.collector', 'donor', 'donationType', 'currency'])
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

        return response()->json($detailed);
    }

    public function exportSummaryReport(Request $request)
    {
        $query = CollectorSession::with('collector')
            ->withCount('donations')
            ->with('donations.currency');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('started_at', [$request->input('start_date'), $request->input('end_date')]);
        }

        if ($request->filled('collector_its')) {
            $query->whereHas('collector', function ($q) use ($request) {
                $q->where('its_id', $request->input('collector_its'));
            });
        }

        $summary = $query->orderBy('started_at', 'desc')
            ->get()
            ->map(function ($session) {
                $total_amount = $session->donations->groupBy('currency.code')
                    ->map(function ($donations, $currency_code) {
                        return [
                            'total' => $donations->sum('amount'),
                            'currency_code' => $currency_code,
                        ];
                    })->values();

                return [
                    'collector_name' => $session->collector->fullname,
                    'session_id' => $session->id,
                    'started_at' => $session->started_at,
                    'ended_at' => $session->ended_at,
                    'total_donations' => $session->donations_count,
                    'total_amount' => $total_amount,
                ];
            });

        return Excel::download(new SummaryReportExport($summary), 'collector-summary-report.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportDetailedReport(Request $request)
    {
        $query = Donation::with(['collectorSession.collector', 'donor', 'donationType', 'currency'])
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

        return Excel::download(new DetailedReportExport($detailed), 'collector-detailed-report.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}
