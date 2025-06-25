<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Event::orderBy('start_date', 'desc')->get();
    }
    /**
     * Store a newly created event in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $event = Event::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json($event, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $event->update($request->all());

        return response()->json($event);
    }

    /**
     * Set the specified event as active.
     */
    public function setActive(Event $event)
    {
        DB::transaction(function () use ($event) {
            // Set all other events to inactive
            Event::where('id', '!=', $event->id)->update(['is_active' => false]);

            // Set the selected event to active
            $event->update(['is_active' => true]);
        });

        return response()->json(['message' => 'Event set to active successfully.']);
    }

    /**
     * Deactivate the specified event.
     */
    public function deactivate(Event $event)
    {
        $event->update(['is_active' => false]);

        return response()->json(['message' => 'Event deactivated successfully.']);
    }
}
