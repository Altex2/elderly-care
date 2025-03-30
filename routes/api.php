<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\VoiceController;
use App\Http\Controllers\Api\CaregiverController;

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
    Route::post('register', [AuthController::class, 'register']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        
        // Caregiver routes
        Route::prefix('caregiver')->middleware('role:caregiver')->group(function () {
            // Patients
            Route::get('/patients', [CaregiverController::class, 'getPatients']);
            Route::post('/patients/create', [CaregiverController::class, 'createPatient']);
            Route::delete('/patients/{patient}', [CaregiverController::class, 'removePatient']);
            Route::put('/patients/{patient}', [CaregiverController::class, 'updatePatient']);
            
            // Reminders
            Route::get('/reminders', [CaregiverController::class, 'getReminders']);
            Route::post('/reminders', [CaregiverController::class, 'createReminder']);
            Route::put('/reminders/{reminder}', [CaregiverController::class, 'updateReminder']);
            Route::delete('/reminders/{reminder}', [CaregiverController::class, 'deleteReminder']);
        });
        
        // Common routes
        Route::post('/reminders/{reminder}/complete', [ReminderController::class, 'complete']);
        Route::post('/voice/process', [VoiceController::class, 'processCommand']);
        Route::post('/voice/speak', [VoiceController::class, 'textToSpeech']);
    });
});
