<?php

use App\Http\Controllers\Account\AccountPageController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\ListingController;
use App\Http\Controllers\Account\MedicineRequestController;
use App\Http\Controllers\Account\MessageController;
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
| Signed-in user pages (design previews)
|--------------------------------------------------------------------------
| These pages show an invented sample account and say so on screen.
| Nothing can be saved yet. In backend push B1 this whole group is wrapped
| in ->middleware('auth') and the pages read the logged-in user's data.
*/
Route::get('/dashboard', DashboardController::class)->name('dashboard');

Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
Route::get('/listings/{listing}/edit', [ListingController::class, 'edit'])->whereNumber('listing')->name('listings.edit');

Route::get('/requests', [MedicineRequestController::class, 'index'])->name('requests.index');
Route::get('/requests/create', [MedicineRequestController::class, 'create'])->name('requests.create');
Route::get('/requests/{medicineRequest}', [MedicineRequestController::class, 'show'])->whereNumber('medicineRequest')->name('requests.show');

Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
Route::get('/messages/{conversation}', [MessageController::class, 'show'])->whereNumber('conversation')->name('messages.show');

Route::get('/saved', [AccountPageController::class, 'saved'])->name('saved.index');
Route::get('/notifications', [AccountPageController::class, 'notifications'])->name('notifications.index');
Route::get('/history', [AccountPageController::class, 'history'])->name('history.index');
Route::get('/verification', [AccountPageController::class, 'verification'])->name('verification.index');
Route::get('/profile', [AccountPageController::class, 'profile'])->name('profile.show');
Route::get('/profile/edit', [AccountPageController::class, 'editProfile'])->name('profile.edit');
Route::get('/settings/privacy', [AccountPageController::class, 'privacy'])->name('settings.privacy');
Route::get('/settings/security', [AccountPageController::class, 'security'])->name('settings.security');

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
