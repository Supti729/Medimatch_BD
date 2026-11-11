<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Design system preview. Registered only on a developer's machine, never in production.
if (app()->environment('local')) {
    Route::view('/styleguide', 'dev.styleguide')->name('styleguide');
}
