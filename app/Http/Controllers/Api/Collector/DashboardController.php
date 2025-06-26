<?php

namespace App\Http\Controllers\Api\Collector;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Donation;

class DashboardController extends Controller
{
    public function getDonations(Request $request)
    {
        $collector = Auth::user();

        // Query donations through the collector's sessions to ensure we only fetch their own records.
        $donations = Donation::whereHas('collectorSession', function ($query) use ($collector) {
                $query->where('its_id', $collector->its_id);
            })
            ->with(['donationType:id,name', 'currency:id,name,code', 'donor'])
            ->orderBy('donated_at', 'desc')
            ->paginate(15);

        return response()->json($donations);
    }
}
