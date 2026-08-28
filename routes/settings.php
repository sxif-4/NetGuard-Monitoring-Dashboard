<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Notifications;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use App\Livewire\Settings\Thresholds;
use App\Livewire\Settings\Users;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', Profile::class)->name('profile.edit');
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::livewire('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::livewire('settings/security', Security::class)
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');

    Route::livewire('settings/thresholds', Thresholds::class)->name('thresholds.edit');

    Route::livewire('settings/notifications', Notifications::class)->name('notifications.edit');

    Route::livewire('settings/users', Users::class)
        ->middleware([
            'password.confirm',
        ])
        ->name('users.index');
});
