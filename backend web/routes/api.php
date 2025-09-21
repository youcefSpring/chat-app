<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CallController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// =========================================================================
// PUBLIC AUTHENTICATION ENDPOINTS
// =========================================================================

Route::prefix('auth')->name('auth.')->group(function () {
    // Authentication endpoints (public)
    Route::post('login', [AuthController::class, 'login'])->name('login')
        ->middleware('throttle:login');
    Route::post('register', [AuthController::class, 'register'])->name('register')
        ->middleware('throttle:login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password')
        ->middleware('throttle:password-reset');
    Route::post('2fa/verify', [AuthController::class, 'verifyTwoFactor'])->name('2fa.verify')
        ->middleware('throttle:2fa');
});

// =========================================================================
// AUTHENTICATED API ENDPOINTS
// =========================================================================

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Authentication (authenticated)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
    });

    // User Management
    Route::prefix('me')->name('me.')->group(function () {
        Route::get('/', [UserController::class, 'me'])->name('show');
        Route::patch('/', [UserController::class, 'update'])->name('update');
        Route::patch('presence', [UserController::class, 'updatePresence'])->name('presence.update');
        Route::post('heartbeat', [UserController::class, 'heartbeat'])->name('heartbeat');
        Route::post('typing', [UserController::class, 'setTyping'])->name('typing.start');
        Route::delete('typing', [UserController::class, 'stopTyping'])->name('typing.stop');
    });

    // Organization Routes
    Route::prefix('organizations/{organization}')->name('organizations.')->group(function () {
        Route::get('/', [OrganizationController::class, 'show'])->name('show');
        Route::patch('/', [OrganizationController::class, 'update'])->name('update');
        Route::get('users', [UserController::class, 'organizationUsers'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'user'])->name('users.show');
        Route::post('invite', [OrganizationController::class, 'inviteUser'])->name('invite');
        Route::get('members', [OrganizationController::class, 'members'])->name('members');

        // Channels within organization
        Route::get('channels', [ChannelController::class, 'index'])->name('channels.index');
        Route::post('channels', [ChannelController::class, 'store'])->name('channels.store');
    });

    // Channel Routes
    Route::prefix('channels/{channel}')->name('channels.')->group(function () {
        Route::get('/', [ChannelController::class, 'show'])->name('show');
        Route::patch('/', [ChannelController::class, 'update'])->name('update');
        Route::delete('/', [ChannelController::class, 'destroy'])->name('destroy');

        // Channel Members
        Route::post('members', [ChannelController::class, 'addMembers'])->name('members.store');
        Route::delete('members/{user}', [ChannelController::class, 'removeMember'])->name('members.destroy');
        Route::post('join', [ChannelController::class, 'join'])->name('join');
        Route::post('leave', [ChannelController::class, 'leave'])->name('leave');

        // Messages within channel
        Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
        Route::post('messages', [MessageController::class, 'store'])->name('messages.store')
            ->middleware('throttle:message');
        Route::post('mark-read', [MessageController::class, 'markAsRead'])->name('mark-read');

        // Online users in channel
        Route::get('online-users', [UserController::class, 'onlineUsers'])->name('online-users');
    });

    // Message Routes
    Route::prefix('messages/{message}')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'show'])->name('show');
        Route::patch('/', [MessageController::class, 'update'])->name('update');
        Route::delete('/', [MessageController::class, 'destroy'])->name('destroy');

        // Reactions
        Route::post('reactions', [MessageController::class, 'addReaction'])->name('reactions.store');
        Route::delete('reactions', [MessageController::class, 'removeReaction'])->name('reactions.destroy');

        // Thread messages
        Route::get('thread', [MessageController::class, 'thread'])->name('thread');
    });

    // File/Attachment Routes
    Route::prefix('attachments')->name('attachments.')->group(function () {
        Route::get('/', [AttachmentController::class, 'index'])->name('index');
        Route::post('/', [AttachmentController::class, 'store'])->name('store')
            ->middleware('throttle:file-upload');
        Route::get('{attachment}', [AttachmentController::class, 'show'])->name('show');
        Route::get('{attachment}/download', [AttachmentController::class, 'download'])->name('download');
        Route::get('{attachment}/thumbnail', [AttachmentController::class, 'thumbnail'])->name('thumbnail');
        Route::delete('{attachment}', [AttachmentController::class, 'destroy'])->name('destroy');
    });

    // Call Routes
    Route::prefix('calls')->name('calls.')->group(function () {
        Route::post('/', [CallController::class, 'store'])->name('store');
        Route::get('{call}', [CallController::class, 'show'])->name('show');
        Route::patch('{call}', [CallController::class, 'update'])->name('update');
        Route::delete('{call}', [CallController::class, 'destroy'])->name('destroy');
    });

    // Search Routes
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/', [SearchController::class, 'search'])->name('index')
            ->middleware('throttle:search');
        Route::get('suggestions', [SearchController::class, 'suggestions'])->name('suggestions');
    });

    // Notification Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::patch('{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('read-all', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    });

    // Admin Routes (restricted to admins only)
    Route::prefix('admin/organizations/{organization}')->name('admin.organizations.')->middleware('can:admin,organization')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs.index');
        Route::get('audit-logs/export', [AdminController::class, 'exportAuditLogs'])->name('audit-logs.export');
    });
});

// =========================================================================
// RATE LIMITING DEFINITIONS
// =========================================================================

/*
 * Rate limits are defined in RouteServiceProvider:
 * - login: 5 requests per minute per IP
 * - message: 60 requests per minute per user
 * - file-upload: 10 requests per minute per user
 * - api: 1000 requests per hour per user
 * - search: 30 requests per minute per user
 * - password-reset: 3 requests per 5 minutes per IP
 * - 2fa: 5 requests per minute per IP
 */