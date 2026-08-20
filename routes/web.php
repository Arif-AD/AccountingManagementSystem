<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'create'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', App\Http\Controllers\DashboardController::class)->name('dashboard');

    Route::get('/accounting/chart-of-accounts', [App\Http\Controllers\Accounting\ChartOfAccountController::class, 'index'])
        ->name('accounting.chart-of-accounts');

    Route::get('/accounting/journal-entries', [App\Http\Controllers\Accounting\JournalEntryController::class, 'index'])
        ->name('accounting.journal-entries.index');

    Route::middleware('role:accountant')->group(function () {
        // Accountant can manage COA
        Route::post('/accounting/chart-of-accounts', [App\Http\Controllers\Accounting\ChartOfAccountController::class, 'store'])
            ->name('accounting.chart-of-accounts.store');
        Route::put('/accounting/chart-of-accounts/{chartOfAccount}', [App\Http\Controllers\Accounting\ChartOfAccountController::class, 'update'])
            ->name('accounting.chart-of-accounts.update');
        Route::delete('/accounting/chart-of-accounts/{chartOfAccount}', [App\Http\Controllers\Accounting\ChartOfAccountController::class, 'destroy'])
            ->name('accounting.chart-of-accounts.destroy');
        Route::get('/accounting/journal-entries/create', [App\Http\Controllers\Accounting\JournalEntryController::class, 'create'])
            ->name('accounting.journal-entries.create');
        Route::post('/accounting/journal-entries', [App\Http\Controllers\Accounting\JournalEntryController::class, 'store'])
            ->name('accounting.journal-entries.store');
    });

    Route::get('/accounting/journal-entries/{journalEntry}', [App\Http\Controllers\Accounting\JournalEntryController::class, 'show'])
        ->name('accounting.journal-entries.show');
});