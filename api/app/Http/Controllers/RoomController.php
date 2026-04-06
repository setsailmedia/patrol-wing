<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'string|in:coop,pvp,ctf',
        ]);

        // Clean up any existing rooms by this host
        GameRoom::where('host_user_id', $request->user()->id)
            ->whereIn('status', ['waiting', 'playing'])
            ->update(['status' => 'finished']);

        $room = GameRoom::create([
            'code' => GameRoom::generateCode(),
            'host_user_id' => $request->user()->id,
            'mode' => $validated['mode'] ?? 'coop',
            'status' => 'waiting',
            'settings' => ['private' => $request->boolean('private', true)],
        ]);

        return response()->json([
            'code' => $room->code,
            'mode' => $room->mode,
            'status' => $room->status,
            'host' => $request->user()->username,
        ], 201);
    }

    public function show(string $code)
    {
        $room = GameRoom::where('code', strtoupper($code))->firstOrFail();
        return response()->json([
            'code' => $room->code,
            'mode' => $room->mode,
            'status' => $room->status,
            'host' => $room->host->username,
            'guest' => $room->guest?->username,
            'host_user_id' => $room->host_user_id,
            'guest_user_id' => $room->guest_user_id,
        ]);
    }

    public function join(Request $request, string $code)
    {
        $room = GameRoom::where('code', strtoupper($code))->where('status', 'waiting')->firstOrFail();

        if ($room->host_user_id === $request->user()->id) {
            return response()->json(['error' => 'Cannot join your own room'], 422);
        }
        if ($room->guest_user_id) {
            return response()->json(['error' => 'Room is full'], 422);
        }

        $room->update(['guest_user_id' => $request->user()->id]);

        return response()->json([
            'code' => $room->code,
            'mode' => $room->mode,
            'status' => $room->status,
            'host' => $room->host->username,
            'guest' => $request->user()->username,
        ]);
    }

    public function update(Request $request, string $code)
    {
        $room = GameRoom::where('code', strtoupper($code))->firstOrFail();
        if ($room->host_user_id !== $request->user()->id) {
            return response()->json(['error' => 'Only host can update'], 403);
        }
        if ($request->has('mode')) $room->mode = $request->input('mode');
        if ($request->has('private')) {
            $settings = $room->settings ?? [];
            $settings['private'] = $request->boolean('private');
            $room->settings = $settings;
        }
        $room->save();
        return response()->json(['status' => 'updated']);
    }

    public function leave(Request $request, string $code)
    {
        $room = GameRoom::where('code', strtoupper($code))->firstOrFail();
        $userId = $request->user()->id;

        if ($room->host_user_id === $userId) {
            $room->update(['status' => 'finished']);
        } elseif ($room->guest_user_id === $userId) {
            $room->update(['guest_user_id' => null]);
        }

        return response()->json(['message' => 'Left room']);
    }

    public function available(Request $request)
    {
        $mode = $request->query('mode');
        $query = GameRoom::where('status', 'waiting')
            ->whereNull('guest_user_id')
            ->where(function ($q) {
                $q->whereNull('settings')
                  ->orWhereRaw("JSON_EXTRACT(settings, '$.private') = false")
                  ->orWhereRaw("JSON_EXTRACT(settings, '$.private') IS NULL");
            })
            ->with('host:id,username')
            ->orderByDesc('created_at')
            ->limit(20);

        if ($mode) $query->where('mode', $mode);

        return response()->json($query->get(['id', 'code', 'mode', 'host_user_id', 'created_at']));
    }
}
