<?php

use App\Http\Controllers\ContactManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Contact management routes
    Route::prefix('contacts')->name('contacts.')->group(function () {
        // Main pages
        Route::get('/', [ContactManagementController::class, 'index'])->name('index');
        Route::get('/{contact}', [ContactManagementController::class, 'show'])->name('show');
        
        // Actions on single contact
        Route::patch('/{contact}/toggle-read', [ContactManagementController::class, 'toggleRead'])->name('toggle-read');
        Route::delete('/{contact}', [ContactManagementController::class, 'destroy'])->name('destroy');
        
        // Bulk actions
        Route::post('/mark-multiple-read', [ContactManagementController::class, 'markMultipleAsRead'])->name('mark-multiple-read');
        Route::delete('/', [ContactManagementController::class, 'destroyMultiple'])->name('destroy-multiple');
        
        // Export
        Route::get('/export/csv', [ContactManagementController::class, 'export'])->name('export');
        
        // API endpoints for frontend
        Route::get('/api/stats', [ContactManagementController::class, 'stats'])->name('stats');
    });
}); 