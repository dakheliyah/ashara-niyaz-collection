<?php

namespace App\Http\Controllers\Api\Collector;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laracsv\Export;

class CollectorReportController extends Controller
{
    public function exportDonations(Request $request)
    {
        $collector = Auth::user();

        $donations = Donation::with(['donor', 'donationType', 'currency'])
            ->whereHas('collectorSession', function ($query) use ($collector) {
                $query->where('collector_id', $collector->id);
            })
            ->orderBy('donated_at', 'desc')
            ->get();

        $csvExporter = new \Laracsv\Export();
        $csvExporter->build($donations, [
            'id' => 'Donation ID',
            'collector_session_id' => 'Session ID',
            'donated_at' => 'Date',
            'donor.fullname' => 'Donor Name',
            'donor_its_id' => 'Donor ITS',
            'donationType.name' => 'Type',
            'amount' => 'Amount',
            'currency.code' => 'Currency',
        ]);

        return $csvExporter->download('my-donations.csv');
    }
}
