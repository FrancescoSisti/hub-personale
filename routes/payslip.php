<?php

declare(strict_types=1);

use App\Http\Controllers\PaySlipController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('pay-slips', [PaySlipController::class, 'index'])
        ->name('pay-slips.index');

    Route::post('pay-slips/upload', [PaySlipController::class, 'upload'])
        ->name('pay-slips.upload');

    Route::get('pay-slips/{paySlip}', [PaySlipController::class, 'show'])
        ->name('pay-slips.show');

    Route::post('pay-slips/{paySlip}/process', [PaySlipController::class, 'process'])
        ->name('pay-slips.process');

    Route::get('pay-slips/{paySlip}/download', [PaySlipController::class, 'download'])
        ->name('pay-slips.download');

    Route::delete('pay-slips/{paySlip}', [PaySlipController::class, 'destroy'])
        ->name('pay-slips.destroy');

    Route::patch('pay-slips/{paySlip}/update-data', [PaySlipController::class, 'updateData'])
        ->name('pay-slips.update-data');
});
