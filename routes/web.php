<?php

use App\Http\Controllers\Account\AccountPageController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\ListingController;
use App\Http\Controllers\Account\MedicineRequestController;
use App\Http\Controllers\Account\MessageController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\ReservationController;
use App\Http\Controllers\Account\SavedListingController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Auth\AccountVerificationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PublicProfileController;
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

// Public profile: only what the user allows others to see
Route::get('/people/{user}', [PublicProfileController::class, 'show'])->whereNumber('user')->name('people.show');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
| Users log in with an email address OR a Bangladeshi phone number.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');

    // Same form as /login, for staff. Role-based redirects happen after login.
    Route::view('/admin/login', 'admin.login')->name('admin.login');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/verify', [AccountVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/verify', [AccountVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/verify/resend', [AccountVerificationController::class, 'resend'])->middleware('throttle:3,1')->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Signed-in user pages
|--------------------------------------------------------------------------
| Require a verified, active account. The account itself is real; most
| records shown on these pages are still sample data until pushes B2-B4,
| and each page says so.
*/
Route::middleware(['auth', 'auth.session', 'active', 'verified.account'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
    Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [ListingController::class, 'edit'])->whereNumber('listing')->name('listings.edit');
    Route::put('/listings/{listing}', [ListingController::class, 'update'])->whereNumber('listing')->name('listings.update');
    Route::patch('/listings/{listing}/status', [ListingController::class, 'changeStatus'])->whereNumber('listing')->name('listings.status');
    Route::delete('/listings/{listing}', [ListingController::class, 'destroy'])->whereNumber('listing')->name('listings.destroy');

    Route::get('/requests', [MedicineRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [MedicineRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [MedicineRequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{medicineRequest}', [MedicineRequestController::class, 'show'])->whereNumber('medicineRequest')->name('requests.show');
    Route::get('/requests/{medicineRequest}/edit', [MedicineRequestController::class, 'edit'])->whereNumber('medicineRequest')->name('requests.edit');
    Route::put('/requests/{medicineRequest}', [MedicineRequestController::class, 'update'])->whereNumber('medicineRequest')->name('requests.update');
    Route::patch('/requests/{medicineRequest}/cancel', [MedicineRequestController::class, 'cancel'])->whereNumber('medicineRequest')->name('requests.cancel');

    // Asking for a listing, and the answers (rules in App\Services\ReservationService)
    Route::post('/medicines/{listing}/reserve', [ReservationController::class, 'store'])->whereNumber('listing')->middleware('throttle:20,1')->name('reservations.store');
    Route::patch('/reservations/{reservation}/accept', [ReservationController::class, 'accept'])->whereNumber('reservation')->name('reservations.accept');
    Route::patch('/reservations/{reservation}/decline', [ReservationController::class, 'decline'])->whereNumber('reservation')->name('reservations.decline');
    Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->whereNumber('reservation')->name('reservations.cancel');
    Route::patch('/reservations/{reservation}/complete', [ReservationController::class, 'complete'])->whereNumber('reservation')->name('reservations.complete');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->whereNumber('conversation')->name('messages.show');

    Route::get('/saved', [SavedListingController::class, 'index'])->name('saved.index');
    Route::post('/saved/{listing}', [SavedListingController::class, 'store'])->whereNumber('listing')->name('saved.store');
    Route::delete('/saved/{listing}', [SavedListingController::class, 'destroy'])->whereNumber('listing')->name('saved.destroy');
    Route::get('/notifications', [AccountPageController::class, 'notifications'])->name('notifications.index');
    Route::get('/history', [AccountPageController::class, 'history'])->name('history.index');
    Route::get('/verification', [AccountPageController::class, 'verification'])->name('verification.index');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/settings/privacy', [ProfileController::class, 'privacy'])->name('settings.privacy');
    Route::put('/settings/privacy', [ProfileController::class, 'updatePrivacy'])->name('settings.privacy.update');
    Route::get('/settings/security', [ProfileController::class, 'security'])->name('settings.security');
    Route::put('/settings/contact', [ProfileController::class, 'updateContact'])->name('settings.contact.update');
    Route::put('/settings/password', [PasswordController::class, 'update'])->name('settings.password.update');
});

/*
|--------------------------------------------------------------------------
| Administration panel
|--------------------------------------------------------------------------
| Administrators only, except the verification pages, which authorized
| reviewers may also open. The pages still show sample data and cannot
| change anything until push B5.
*/
Route::prefix('admin')->name('admin.')->controller(AdminPageController::class)
    ->middleware(['auth', 'auth.session', 'active', 'verified.account'])
    ->group(function () {
        Route::middleware('role:admin,reviewer')->group(function () {
            Route::get('/verifications', 'verifications')->name('verifications.index');
            Route::get('/verifications/{verification}', 'verification')->whereNumber('verification')->name('verifications.show');
        });

        Route::middleware('role:admin')->group(function () {
            Route::get('/', 'dashboard')->name('dashboard');
            Route::get('/users', 'users')->name('users.index');
            Route::get('/users/{user}', 'user')->whereNumber('user')->name('users.show');
            Route::get('/medicines', 'medicines')->name('medicines.index');
            Route::get('/listings', 'listings')->name('listings.index');
            Route::get('/listings/{listing}', 'listing')->whereNumber('listing')->name('listings.show');
            Route::get('/requests', 'requests')->name('requests.index');
            Route::get('/reports', 'reports')->name('reports.index');
            Route::get('/reports/{report}', 'report')->whereNumber('report')->name('reports.show');
            Route::get('/transactions', 'transactions')->name('transactions.index');
            Route::get('/partners', 'partners')->name('partners.index');
            Route::get('/notifications', 'notifications')->name('notifications.index');
            Route::get('/audit-logs', 'auditLogs')->name('audit-logs.index');
            Route::get('/settings', 'settings')->name('settings');
        });
    });

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
