<?php

use Illuminate\Support\Facades\Route;
use Modules\Backups\Http\Controllers\BackupsController;

Route::prefix('backups')->name('admin.')->group(function () {
    Route::get('/', [BackupsController::class, 'index'])->name('backups.index');
    Route::get('/clear-logs', [BackupsController::class, 'clearLogs'])->name('backups.logs-clear');
    Route::post('/settings', [BackupsController::class, 'settings'])->name('backups.settings');
    Route::get('/create', [BackupsController::class, 'create'])->name('backups.create');
    Route::get('/download/{name}', [BackupsController::class, 'download'])->name('backups.download');
    Route::delete('/delete/{name}', [BackupsController::class, 'delete'])->name('backups.delete');
})->middleware('permission');
