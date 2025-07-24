<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $currentYear = now()->year;

    $currentMonthSalary = \App\Models\Salary::forUser($user->id)
        ->forMonth(now()->month, $currentYear)
        ->first();

    $yearSalaries = \App\Models\Salary::forUser($user->id)
        ->forYear($currentYear)
        ->get();

    $salaryData = [
        'currentMonthSalary' => $currentMonthSalary,
        'yearlyTotal' => $yearSalaries->sum('net_salary'),
        'monthsRecorded' => $yearSalaries->count(),
        'averageMonthly' => $yearSalaries->count() > 0 ? $yearSalaries->avg('net_salary') : 0,
    ];

    return Inertia::render('Dashboard', [
        'salaryData' => $salaryData,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/salary.php';
require __DIR__ . '/payslip.php';
require __DIR__ . '/contact.php';
require __DIR__ . '/auth.php';
