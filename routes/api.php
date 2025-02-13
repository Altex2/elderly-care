<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\VoiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        
        // Reminders
        Route::get('reminders', [ReminderController::class, 'index']);
        Route::post('reminders/{reminder}/complete', [ReminderController::class, 'complete']);
        
        // Voice commands
        Route::post('voice/process', [VoiceController::class, 'processCommand']);
        Route::post('voice/speak', [VoiceController::class, 'textToSpeech']);
    });
});
