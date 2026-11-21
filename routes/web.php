<?php

use App\Http\Controllers\CrimeCaseController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/cases', [CrimeCaseController::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [CrimeCaseController::class, 'create'])->name('cases.create');
    Route::get('/cases/{case}', [CrimeCaseController::class, 'show'])->name('cases.show');
    Route::patch('/cases/{case}/assignment', [CrimeCaseController::class, 'assign'])->name('cases.assign');
    Route::patch('/cases/{case}/investigation', [CrimeCaseController::class, 'updateInvestigation'])->name('cases.update_investigation');
    Route::patch('/cases/{case}/judgment', [CrimeCaseController::class, 'recordJudgment'])->name('cases.record_judgment');
    Route::patch('/cases/{case}/close', [CrimeCaseController::class, 'close'])->name('cases.close');
    Route::post('/cases', [CrimeCaseController::class, 'store'])->name('cases.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
