<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
| Developer previews of the design system, components and layouts.
| Registered only on a developer's machine (APP_ENV=local), never in production.
*/
if (app()->environment('local')) {
    Route::prefix('styleguide')->group(function () {
        Route::view('/', 'dev.styleguide')->name('styleguide');
        Route::view('/components', 'dev.components')->name('styleguide.components');
        Route::view('/public', 'dev.preview-public')->name('styleguide.public');
        Route::view('/auth', 'dev.preview-auth')->name('styleguide.auth');
        Route::view('/app', 'dev.preview-app')->name('styleguide.app');
        Route::view('/admin', 'dev.preview-admin')->name('styleguide.admin');
    });
}
