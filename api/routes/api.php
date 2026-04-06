<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

// Broadcasting auth (outside v1 prefix for Reverb compatibility)
Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
    return \Illuminate\Support\Facades\Broadcast::auth($request);
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Auth (rate limited)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Scores
        Route::post('/scores', [ScoreController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/scores/me', [ScoreController::class, 'mine']);

        // Events
        Route::post('/events', [EventController::class, 'store'])->middleware('throttle:60,1');
        Route::post('/events/batch', [EventController::class, 'batch'])->middleware('throttle:60,1');

        // Rooms
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::get('/rooms/available', [RoomController::class, 'available']);
        Route::get('/rooms/{code}', [RoomController::class, 'show']);
        Route::post('/rooms/{code}/join', [RoomController::class, 'join']);
        Route::patch('/rooms/{code}', [RoomController::class, 'update']);
        Route::delete('/rooms/{code}', [RoomController::class, 'leave']);
    });

    // Public leaderboard
    Route::get('/scores', [ScoreController::class, 'index'])->middleware('throttle:120,1');
});
