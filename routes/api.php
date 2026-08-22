<?php

use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'ability:monitoring:write'])
    ->prefix('monitoring')
    ->group(function () {
        Route::get('devices', [MonitoringController::class, 'devices'])->name('api.monitoring.devices');
        Route::post('metrics', [MonitoringController::class, 'storeMetrics'])->name('api.monitoring.metrics');
        Route::post('security-events', [SecurityController::class, 'storeEvents'])->name('api.monitoring.security-events');
    });
