<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CaregiverController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoiceController;
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

// Caregiver Routes
Route::middleware(['auth', 'role:caregiver'])->group(function () {
    Route::get('/caregiver/dashboard', [CaregiverController::class, 'dashboard'])
        ->name('caregiver.dashboard');

    // Patient and Reminder management
    Route::get('/caregiver/reminders', [CaregiverController::class, 'reminders'])
        ->name('caregiver.reminders');
    Route::post('/caregiver/patients/create', [CaregiverController::class, 'createPatient'])
        ->name('caregiver.patients.create');
    Route::delete('/caregiver/patients/{patient}', [CaregiverController::class, 'removePatient'])
        ->name('caregiver.patients.remove');
    Route::post('/caregiver/reminders', [CaregiverController::class, 'createReminder'])
        ->name('caregiver.reminders.create');
    Route::put('/caregiver/reminders/{reminder}', [CaregiverController::class, 'updateReminder'])
        ->name('caregiver.reminders.update');
    Route::delete('/caregiver/reminders/{reminder}', [CaregiverController::class, 'deleteReminder'])
        ->name('caregiver.reminders.delete');
});

// Patient Routes
Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])
        ->name('user.dashboard');
    Route::get('/voice', [VoiceController::class, 'index'])
        ->name('voice.interface');
    Route::post('/voice/process', [VoiceController::class, 'processVoice'])
        ->name('voice.process');
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

require __DIR__.'/auth.php';
