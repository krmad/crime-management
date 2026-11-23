<?php

use App\Http\Controllers\CaseSuspectController;
use App\Http\Controllers\CaseVictimController;
use App\Http\Controllers\CrimeCaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OfficerProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuspectController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VictimController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/cases', [CrimeCaseController::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [CrimeCaseController::class, 'create'])->name('cases.create');
    Route::get('/cases/{case}', [CrimeCaseController::class, 'show'])->name('cases.show');
    Route::patch('/cases/{case}/assignment', [CrimeCaseController::class, 'assign'])->name('cases.assign');
    Route::patch('/cases/{case}/investigation', [CrimeCaseController::class, 'updateInvestigation'])->name('cases.update_investigation');
    Route::patch('/cases/{case}/judgment', [CrimeCaseController::class, 'recordJudgment'])->name('cases.record_judgment');
    Route::patch('/cases/{case}/close', [CrimeCaseController::class, 'close'])->name('cases.close');
    Route::post('/cases', [CrimeCaseController::class, 'store'])->name('cases.store');

    Route::post('/cases/{case}/suspects/attach', [CaseSuspectController::class, 'attach'])->name('cases.suspects.attach');
    Route::post('/cases/{case}/suspects', [CaseSuspectController::class, 'storeNew'])->name('cases.suspects.store_new');
    Route::patch('/cases/{case}/suspects/{suspect}', [CaseSuspectController::class, 'update'])->name('cases.suspects.update');

    Route::post('/cases/{case}/victims/attach', [CaseVictimController::class, 'attach'])->name('cases.victims.attach');
    Route::post('/cases/{case}/victims', [CaseVictimController::class, 'storeNew'])->name('cases.victims.store_new');
    Route::patch('/cases/{case}/victims/{victim}', [CaseVictimController::class, 'update'])->name('cases.victims.update');

    Route::resource('suspects', SuspectController::class)->only(['index', 'create', 'store', 'edit', 'update', 'show']);
    Route::resource('victims', VictimController::class)->only(['index', 'create', 'store', 'edit', 'update', 'show']);

    Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');

    Route::resource('officers', OfficerProfileController::class)
        ->parameters(['officers' => 'officerProfile'])
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
