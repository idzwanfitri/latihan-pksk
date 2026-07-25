<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:pelajar'])->group(function () {
    Route::livewire('ujian', 'pages::ujian.mula')->name('ujian.mula');
    Route::livewire('ujian/{attempt}', 'pages::ujian.jawab')->name('ujian.jawab');
    Route::livewire('ujian/{attempt}/keputusan', 'pages::ujian.keputusan')->name('ujian.keputusan');
});

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::redirect('/', '/admin/soalan-a');
        Route::livewire('soalan-a', 'pages::admin.soalan-a')->name('soalan-a');
        Route::livewire('soalan-b', 'pages::admin.soalan-b')->name('soalan-b');
    });

require __DIR__.'/settings.php';
