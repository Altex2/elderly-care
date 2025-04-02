<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CaregiverController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoiceController;
use App\Http\Controllers\VoiceCommandController;
use App\Http\Controllers\ReminderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Authentication routes are handled by auth.php

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Default dashboard redirect based on role
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->isCaregiver()) {
            return redirect()->route('caregiver.dashboard');
        }
        return redirect()->route('user.dashboard');
    })->name('dashboard');

    // User routes
    Route::middleware(['role:user'])->group(function () {
        Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
        Route::post('/voice/process', [VoiceCommandController::class, 'processCommand'])->name('voice.process');
        Route::get('/voice', function () {
            return view('voice.interface');
        })->name('voice.interface');
        Route::post('/reminders/{reminder}/complete', [UserController::class, 'completeReminder'])->name('reminders.complete');
    });

    // Caregiver routes
    Route::middleware(['role:caregiver'])->group(function () {
        Route::get('/caregiver/dashboard', [CaregiverController::class, 'dashboard'])->name('caregiver.dashboard');
        Route::get('/caregiver/patients', [CaregiverController::class, 'patients'])->name('caregiver.patients');
        Route::get('/caregiver/reminders', [CaregiverController::class, 'reminders'])->name('caregiver.reminders');
        Route::post('/caregiver/patients/create', [CaregiverController::class, 'createPatient'])->name('caregiver.patients.create');
        Route::delete('/caregiver/patients/{patient}', [CaregiverController::class, 'removePatient'])->name('caregiver.patients.remove');
        Route::post('/caregiver/reminders', [CaregiverController::class, 'createReminder'])->name('caregiver.reminders.create');
        Route::put('/caregiver/reminders/{reminder}', [CaregiverController::class, 'updateReminder'])->name('caregiver.reminders.update');
        Route::delete('/caregiver/reminders/{reminder}', [CaregiverController::class, 'deleteReminder'])->name('caregiver.reminders.delete');
    });

    // Common routes (accessible by both roles)
    Route::resource('reminders', ReminderController::class);
    Route::post('/reminders/{reminder}/complete', [ReminderController::class, 'complete'])->name('reminders.complete');
    Route::get('/voice', [VoiceController::class, 'index'])->name('voice.interface');
    Route::post('/voice/process-audio', [VoiceController::class, 'processAudio'])->name('voice.process-audio');
    Route::post('/voice/process-command', [VoiceController::class, 'processCommand'])->name('voice.process-command');
});

// Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Test Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/test/dashboard', [TestController::class, 'index'])->name('test.dashboard');
    Route::post('/test/voice', [TestController::class, 'testVoice'])->name('test.voice');
    Route::post('/test/notification', [TestController::class, 'testNotification'])->name('test.notification');
    Route::post('/artisan/reminders:process', function () {
        Artisan::call('reminders:process');
        return response()->json(['message' => 'Reminders processed']);
    })->name('test.process-reminders');
});

// Voice command routes
Route::middleware(['auth'])->group(function () {
    Route::get('/voice', function () {
        return view('voice.interface');
    })->name('voice.interface');
    Route::get('/voice/test', [VoiceCommandController::class, 'test'])->name('voice.test');
});

require __DIR__.'/auth.php';
