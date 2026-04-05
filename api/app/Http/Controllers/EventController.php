<?php

namespace App\Http\Controllers;

use App\Models\GameEvent;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:50',
            'payload' => 'nullable|array',
            'timestamp' => 'nullable|integer',
        ]);

        $event = $request->user()->gameEvents()->create([
            'event_type' => $validated['event_type'],
            'payload' => $validated['payload'] ?? null,
            'client_timestamp' => $validated['timestamp'] ?? null,
        ]);

        return response()->json(['id' => $event->id], 201);
    }

    public function batch(Request $request)
    {
        $request->validate([
            '*.event_type' => 'required|string|max:50',
            '*.payload' => 'nullable|array',
            '*.timestamp' => 'nullable|integer',
        ]);

        $events = $request->all();
        if (count($events) > 100) {
            return response()->json(['error' => 'Max 100 events per batch'], 422);
        }

        $userId = $request->user()->id;
        $rows = [];
        $now = now();

        foreach ($events as $e) {
            $rows[] = [
                'user_id' => $userId,
                'event_type' => $e['event_type'],
                'payload' => isset($e['payload']) ? json_encode($e['payload']) : null,
                'client_timestamp' => $e['timestamp'] ?? null,
                'created_at' => $now,
            ];
        }

        GameEvent::insert($rows);

        return response()->json(['count' => count($rows)], 201);
    }
}
