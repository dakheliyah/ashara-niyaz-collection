<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollectorSession;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = CollectorSession::with(['collector.role', 'donations', 'event'])
            ->orderBy('session_date', 'desc')
            ->get();

        $sessions->each(function ($session) {
            // Calculate total collected amount for this session
            $session->total_collected = $session->donations->sum('amount');
            
            // Add collector name for easier frontend access
            $session->collector_name = $session->collector ? $session->collector->its_id : 'Unknown';
            
            // Format dates
            $session->formatted_start_time = $session->started_at ? $session->started_at->format('Y-m-d H:i:s') : null;
            $session->formatted_end_time = $session->ended_at ? $session->ended_at->format('Y-m-d H:i:s') : null;
        });

        return response()->json($sessions);
    }
}
