<?php

use App\Livewire\Alerts\Index as AlertsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Devices\Index as DevicesIndex;
use App\Livewire\Devices\Show as DevicesShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');

    Route::middleware('admin')->group(function () {
        Route::livewire('devices', DevicesIndex::class)->name('devices.index');
        Route::livewire('devices/{device}', DevicesShow::class)->name('devices.show');
        Route::livewire('alerts', AlertsIndex::class)->name('alerts.index');
    });
});

require __DIR__.'/settings.php';
