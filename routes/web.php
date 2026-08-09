<?php

use Illuminate\Support\Facades\Route;
use WizballEsy\LibreNmsOxidizedHistory\Http\Controllers\HistoricalConfigTabController;

Route::middleware(['web', 'auth'])
    ->prefix('plugin/oxidized-history/device/{device}')
    ->name('oxidized-history.')
    ->group(function (): void {
        Route::get('backups', [HistoricalConfigTabController::class, 'backups'])
            ->name('backups');

        Route::get('backup', [HistoricalConfigTabController::class, 'backup'])
            ->name('backup');

        Route::get('diff', [HistoricalConfigTabController::class, 'diff'])
            ->name('diff');

        Route::post('take-backup', [HistoricalConfigTabController::class, 'takeBackup'])
            ->name('take-backup');

        Route::get('backup-status', [HistoricalConfigTabController::class, 'backupStatus'])
            ->name('backup-status');
    });
