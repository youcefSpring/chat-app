<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\ChannelController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// =========================================================================
// PUBLIC ROUTES (No Authentication Required)
// =========================================================================

// Redirect root to login or dashboard
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

// =========================================================================
// AUTHENTICATION ROUTES
// =========================================================================

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('register', [AuthenticatedSessionController::class, 'createRegister'])->name('register');
    Route::post('register', [AuthenticatedSessionController::class, 'storeRegister']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// =========================================================================
// AUTHENTICATED WEB ROUTES
// =========================================================================

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/chat', [DashboardController::class, 'chat'])->name('chat');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');

    // Channel Routes
    Route::prefix('channels')->name('channels.')->group(function () {
        Route::get('/', [ChannelController::class, 'index'])->name('index');
        Route::get('/create', [ChannelController::class, 'create'])->name('create');
        Route::post('/', [ChannelController::class, 'store'])->name('store');
        Route::get('/{channel}', [ChannelController::class, 'show'])->name('show');
        Route::get('/{channel}/edit', [ChannelController::class, 'edit'])->name('edit');
        Route::patch('/{channel}', [ChannelController::class, 'update'])->name('update');
        Route::delete('/{channel}', [ChannelController::class, 'destroy'])->name('destroy');

        // Channel Actions
        Route::post('/{channel}/join', [ChannelController::class, 'join'])->name('join');
        Route::post('/{channel}/leave', [ChannelController::class, 'leave'])->name('leave');
        Route::get('/{channel}/members', [ChannelController::class, 'members'])->name('members');
    });

    // Settings Routes
    Route::prefix('settings')->name('settings.')->group(function () {
        // Profile Settings
        Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
        Route::patch('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');

        // Security Settings
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::patch('/security/password', [SettingsController::class, 'updatePassword'])->name('password.update');

        // Two-Factor Authentication
        Route::get('/two-factor', [SettingsController::class, 'twoFactor'])->name('two-factor');
        Route::post('/two-factor/enable', [SettingsController::class, 'enableTwoFactor'])->name('two-factor.enable');
        Route::delete('/two-factor/disable', [SettingsController::class, 'disableTwoFactor'])->name('two-factor.disable');

        // Notification Settings
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::patch('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');

        // Organization Settings (Admin Only)
        Route::middleware('can:admin,organization')->group(function () {
            Route::get('/organization', [SettingsController::class, 'organization'])->name('organization');
            Route::patch('/organization', [SettingsController::class, 'updateOrganization'])->name('organization.update');
        });

        // Admin Dashboard (Admin Only)
        Route::middleware('can:admin,organization')->group(function () {
            Route::get('/admin', [SettingsController::class, 'admin'])->name('admin');
        });
    });
});

// =========================================================================
// SPA ROUTES (for Vue.js frontend)
// =========================================================================

// Single Page Application catch-all route for Vue.js router
Route::get('/app/{any}', function () {
    return view('spa.app');
})->where('any', '.*')->name('spa');

// =========================================================================
// WEBSOCKET ROUTES (for Pusher/WebSocket integration)
// =========================================================================

// WebSocket authentication endpoint
Route::middleware('auth')->post('/broadcasting/auth', function () {
    return response()->json(['auth' => auth()->user()]);
})->name('broadcasting.auth');

// =========================================================================
// ERROR PAGES
// =========================================================================

// Custom error pages
Route::get('/403', function () {
    abort(403);
})->name('error.403');

Route::get('/404', function () {
    abort(404);
})->name('error.404');

Route::get('/500', function () {
    abort(500);
})->name('error.500');