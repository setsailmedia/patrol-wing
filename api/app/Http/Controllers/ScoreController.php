<?php

namespace App\Http\Controllers;

use App\Models\Score;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|string|in:battle,timetrial,combattraining,custom',
            'score' => 'required|integer|min:0',
            'duration_ms' => 'required|integer|min:0',
            'wave_reached' => 'integer|min:0|max:255',
            'craft_id' => 'required|string|max:30',
            'level_name' => 'nullable|string|max:100',
        ]);

        $score = $request->user()->scores()->create($validated);

        return response()->json($score, 201);
    }

    public function index(Request $request)
    {
        $mode = $request->query('mode');
        $limit = min(100, max(1, (int) ($request->query('limit', 20))));

        $query = Score::query()
            ->join('users', 'scores.user_id', '=', 'users.id')
            ->select('scores.id', 'users.username', 'scores.mode', 'scores.score', 'scores.duration_ms', 'scores.craft_id', 'scores.created_at')
            ->orderByDesc('scores.score');

        if ($mode) {
            $query->where('scores.mode', $mode);
        }

        return response()->json($query->limit($limit)->get());
    }

    public function mine(Request $request)
    {
        $limit = min(100, max(1, (int) ($request->query('limit', 20))));

        $scores = $request->user()->scores()
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return response()->json($scores);
    }
}
