<?php

namespace App\Http\Controllers\Api;

use App\Models\CollectorSession;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CollectorSessionController extends Controller
{
    public function startSession(Request $request)
    {
        $user = $request->attributes->get('admin');
        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }
        $itsId = $user->its_id;
        $today = Carbon::today();

        // Check for an existing active session for the same ITS ID on the same day
        $existingSession = CollectorSession::where('its_id', $itsId)
            ->whereDate('session_date', $today)
            ->where('status', 'active')
            ->first();

        if ($existingSession) {
            return response()->json(['message' => 'An active session for today already exists.'], 409); // 409 Conflict
        }

        // Find the currently active event
        $event = Event::where('is_active', true)->first();
        if (!$event) {
            return response()->json(['message' => 'No active event found.'], 404);
        }

        $session = CollectorSession::create([
            'its_id' => $itsId,
            'event_id' => $event->id,
            'session_date' => $today,
            'started_at' => now(),
            'status' => 'active',
        ]);

        return response()->json($session, 201); // 201 Created
    }

    public function endSession(Request $request)
    {
        $user = $request->attributes->get('admin');
        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }
        $itsId = $user->its_id;

        // Find the current active session for this collector
        $session = CollectorSession::where('its_id', $itsId)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            return response()->json(['message' => 'No active session found to end.'], 404);
        }

        $session->update([
            'ended_at' => now(),
            'status' => 'closed',
        ]);

        return response()->json($session);
    }

    public function status(Request $request)
    {
        $user = $request->attributes->get('admin');
        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }
        $itsId = $user->its_id;
        $today = Carbon::today();

        $session = CollectorSession::where('its_id', $itsId)
            ->whereDate('session_date', $today)
            ->where('status', 'active')
            ->first();

        if ($session) {
            return response()->json($session);
        }

        return response()->json(['message' => 'No active session found for today.'], 404);
    }

}
