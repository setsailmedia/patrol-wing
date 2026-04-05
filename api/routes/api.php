<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

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
    });

    // Public leaderboard
    Route::get('/scores', [ScoreController::class, 'index'])->middleware('throttle:120,1');
});
