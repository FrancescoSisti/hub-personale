<?php

declare(strict_types=1);

use App\Http\Controllers\SalaryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('salaries', SalaryController::class);

    Route::get('salaries/statistics/{year?}', [SalaryController::class, 'statistics'])
        ->name('salaries.statistics');

    Route::get('salaries/trends/{startYear?}/{endYear?}', [SalaryController::class, 'trends'])
        ->name('salaries.trends');

    Route::get('salaries/top-earning-months/{limit?}', [SalaryController::class, 'topEarningMonths'])
        ->name('salaries.top-earning-months');
});
