<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlacklistController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\MessageLogController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SegmentController;
use App\Http\Controllers\Api\V1\SmartSegmentController;
use App\Http\Controllers\Api\V1\SuppressionController;
use App\Http\Controllers\Api\V1\SystemHealthController;
use App\Http\Controllers\Api\V1\SystemSettingController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\TemplateCategoryController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ── Auth (public) ─────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth');
        Route::post('otp/verify', [AuthController::class, 'otpVerify'])
            ->middleware('throttle:auth-otp');
        Route::post('otp/resend', [AuthController::class, 'otpResend'])
            ->middleware('throttle:auth');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:auth');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:auth');
    });

    // ── Authenticated ─────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', \App\Http\Middleware\EnsureApiUserIsActive::class, 'throttle:api'])->group(function () {
        Route::post('auth/logout',     [AuthController::class, 'logout']);
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Campaigns
        Route::apiResource('campaigns', CampaignController::class);
        Route::post('campaigns/{campaign}/send', [CampaignController::class, 'sendNow']);
        Route::post('campaigns/{campaign}/resend-failed', [CampaignController::class, 'resendFailed']);
        Route::post('campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate']);
        Route::get('campaigns/{campaign}/export', [CampaignController::class, 'exportReport']);
        Route::get('campaigns/{campaign}/messages', [CampaignController::class, 'messages']);
        Route::get('campaigns/{campaign}/clicked-contacts', [CampaignController::class, 'clickedContacts']);
        Route::get('campaigns/{campaign}/non-clicked-contacts', [CampaignController::class, 'nonClickedContacts']);
        Route::post('campaigns/{campaign}/create-segment-from-clicks', [CampaignController::class, 'createSegmentFromClicks']);

        // Contacts — literal routes MUST come before apiResource to avoid {contact} capture
        Route::post('contacts/import',         [ContactController::class, 'import'])->name('contacts.import')->middleware('throttle:import');
        Route::get('contacts/export',          [ContactController::class, 'export'])->name('contacts.export')->middleware('throttle:export');
        Route::get('contacts/check-duplicate', [ContactController::class, 'checkDuplicate'])->name('contacts.check-duplicate');
        Route::post('contacts/bulk-delete',    [ContactController::class, 'bulkDelete'])->name('contacts.bulk-delete');
        Route::post('contacts/bulk-update',    [ContactController::class, 'bulkUpdate'])->name('contacts.bulk-update');
        Route::post('contacts/bulk-suppress',  [ContactController::class, 'bulkSuppress'])->name('contacts.bulk-suppress');
        Route::get('contacts/filter-count',    [ContactController::class, 'filterCount'])->name('contacts.filter-count');
        Route::get('contacts/analytics/country',  [ContactController::class, 'analyticsCountry'])->name('contacts.analytics.country');
        Route::get('contacts/analytics/language', [ContactController::class, 'analyticsLanguage'])->name('contacts.analytics.language');
        Route::apiResource('contacts', ContactController::class);
        Route::post('contacts/{contact}/toggle-opt-in', [ContactController::class, 'toggleOptIn'])->name('contacts.toggle-opt-in');
        Route::get('contacts/{contact}/notes',          [ContactController::class, 'notes'])->name('contacts.notes.index');
        Route::post('contacts/{contact}/notes',         [ContactController::class, 'storeNote'])->name('contacts.notes.store');
        Route::get('contacts/{contact}/messages',       [ContactController::class, 'contactMessages'])->name('contacts.messages');

        // Tags
        Route::apiResource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);

        // Segments — literal routes before apiResource
        Route::post('segments/preview', [SegmentController::class, 'preview'])->name('segments.preview');
        Route::apiResource('segments', SegmentController::class);
        Route::get('segments/{segment}/contacts', [SegmentController::class, 'contacts']);
        Route::get('segments/{segment}/count', [SegmentController::class, 'count']);

        // Templates
        Route::apiResource('templates', TemplateController::class);
        Route::apiResource('template-categories', TemplateCategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        // Reports
        Route::get('reports/costs',     [ReportController::class, 'costs']);
        Route::get('reports/countries', [ReportController::class, 'countries']);
        Route::get('reports/languages', [ReportController::class, 'languages']);
        Route::get('reports/delivery',  [ReportController::class, 'delivery']);
        Route::get('reports/summary',   [ReportController::class, 'summary']);

        // Suppression list (admin only)
        Route::get('suppression',              [SuppressionController::class, 'index']);
        Route::post('suppression',             [SuppressionController::class, 'store']);
        Route::delete('suppression/{suppressionList}', [SuppressionController::class, 'destroy']);
        Route::get('suppression/stats',        [SuppressionController::class, 'stats']);

        // Smart segmentation (admin only)
        Route::post('smart-segments/preview',    [SmartSegmentController::class, 'preview']);
        Route::post('smart-segments/distribute', [SmartSegmentController::class, 'distribute']);

        // Users (admin only)
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive']);

        // Profile
        Route::get('profile',                  [ProfileController::class, 'show']);
        Route::put('profile',                  [ProfileController::class, 'update']);
        Route::put('profile/password',         [ProfileController::class, 'updatePassword']);

        // Audit Logs (admin only)
        Route::get('audit-logs',               [AuditLogController::class, 'index']);

        // Global Blacklist (admin only)
        Route::get('blacklist',                [BlacklistController::class, 'index']);
        Route::post('blacklist',               [BlacklistController::class, 'store']);
        Route::delete('blacklist/{globalBlacklist}', [BlacklistController::class, 'destroy']);

        // Global Search
        Route::get('search',                   SearchController::class);

        // Message Log Center (admin and staff, phones masked for staff)
        Route::get('messages',         [MessageLogController::class, 'index']);
        Route::get('messages/{message}', [MessageLogController::class, 'show']);

        // System Health (admin only)
        Route::get('system/health',    [SystemHealthController::class, 'index']);

        // System Settings (admin only)
        Route::get('system/settings',        [SystemSettingController::class, 'index']);
        Route::put('system/settings/{key}',  [SystemSettingController::class, 'update']);
        Route::post('system/settings/bulk',  [SystemSettingController::class, 'bulkUpdate']);
    });
});
