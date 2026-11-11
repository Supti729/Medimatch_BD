<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MedicineController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');

Route::get('/medicines', [MedicineController::class, 'index'])->name('medicines.index');
Route::get('/medicines/{listing}', [MedicineController::class, 'show'])->whereNumber('listing')->name('medicines.show');

Route::view('/how-it-works', 'pages.how-it-works')->name('how-it-works');
Route::view('/safety', 'pages.safety')->name('safety');
Route::view('/about', 'pages.about')->name('about');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/terms', 'pages.terms')->name('terms');

/*
|--------------------------------------------------------------------------
| Sign-in screens (design only)
|--------------------------------------------------------------------------
| These routes only show the forms. The routes that process them
| (login.store, register.store, password.email, password.update,
| verification.verify) are added with authentication in backend push B1.
| Until then each form explains that it is not connected yet.
*/
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
Route::get('/reset-password/{token}', fn (string $token) => view('auth.reset-password', ['token' => $token]))->name('password.reset');
Route::view('/verify', 'auth.verify')->name('verification.notice');

/*
|--------------------------------------------------------------------------
| Developer previews
|--------------------------------------------------------------------------
| Design system, components and layouts.
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
