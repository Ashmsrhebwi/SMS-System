<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactNoteController;
use App\Http\Controllers\CostReportController;
use App\Http\Controllers\CountryAnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OptOutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TemplateCategoryController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/spa'));

// Public routes
Route::get('/r/{token}', [TrackingController::class, 'click'])->name('track.click');
Route::get('/optout', [OptOutController::class, 'form'])->name('optout.form');
Route::post('/optout', [OptOutController::class, 'process'])->name('optout.process');

// Twilio webhook — public but signature-validated inside controller
Route::post('/webhooks/twilio/status', [WebhookController::class, 'twilioStatus'])
    ->name('webhooks.twilio.status');

// Authenticated routes
Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureUserIsActive::class])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Global Search (AJAX)
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // ─── Contacts ─────────────────────────────────────────────────────────────
    Route::get('/contacts',                      [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/create',               [ContactController::class, 'create'])->name('contacts.create');
    Route::post('/contacts',                     [ContactController::class, 'store'])->name('contacts.store');
    Route::get('/contacts/import',               [ContactController::class, 'importForm'])->name('contacts.import');
    Route::post('/contacts/import',              [ContactController::class, 'import'])->name('contacts.import.store');
    Route::get('/contacts/export',               [ContactController::class, 'export'])->name('contacts.export');
    Route::get('/contacts/check-duplicate',      [ContactController::class, 'checkDuplicate'])->name('contacts.check-duplicate');
    Route::get('/contacts/{contact}',            [ContactController::class, 'show'])->name('contacts.show');
    Route::get('/contacts/{contact}/edit',       [ContactController::class, 'edit'])->name('contacts.edit');
    Route::put('/contacts/{contact}',            [ContactController::class, 'update'])->name('contacts.update');
    Route::patch('/contacts/{contact}/toggle-opt-in', [ContactController::class, 'toggleOptIn'])->name('contacts.toggle-opt-in');
    Route::delete('/contacts/{contact}',         [ContactController::class, 'destroy'])->name('contacts.destroy');

    // Contact Notes
    Route::post('/contacts/{contact}/notes',              [ContactNoteController::class, 'store'])->name('contacts.notes.store');
    Route::put('/contacts/{contact}/notes/{note}',        [ContactNoteController::class, 'update'])->name('contacts.notes.update');
    Route::delete('/contacts/{contact}/notes/{note}',     [ContactNoteController::class, 'destroy'])->name('contacts.notes.destroy');

    // ─── Campaigns ────────────────────────────────────────────────────────────
    Route::get('/campaigns',                      [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create',               [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns',                     [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}',           [CampaignController::class, 'show'])->name('campaigns.show');
    Route::post('/campaigns/{campaign}/send-now', [CampaignController::class, 'sendNow'])->name('campaigns.send-now');
    Route::post('/campaigns/{campaign}/duplicate',[CampaignController::class, 'duplicate'])->name('campaigns.duplicate');
    Route::post('/campaigns/{campaign}/resend-failed', [CampaignController::class, 'resendFailed'])->name('campaigns.resend-failed');
    Route::get('/campaigns/{campaign}/export',    [CampaignController::class, 'exportReport'])->name('campaigns.export');
    Route::delete('/campaigns/{campaign}',        [CampaignController::class, 'destroy'])->name('campaigns.destroy');

    // ─── Tags (admin only via policy) ─────────────────────────────────────────
    Route::resource('tags', TagController::class)->except(['show']);

    // ─── Segments (admin only via policy) ─────────────────────────────────────
    Route::resource('segments', SegmentController::class)->except(['show']);

    // ─── SMS Templates + Categories (admin only via policy) ──────────────────
    Route::resource('templates', SmsTemplateController::class)->except(['show']);
    Route::get('/template-categories',                      [TemplateCategoryController::class, 'index'])->name('template-categories.index');
    Route::post('/template-categories',                     [TemplateCategoryController::class, 'store'])->name('template-categories.store');
    Route::put('/template-categories/{templateCategory}',   [TemplateCategoryController::class, 'update'])->name('template-categories.update');
    Route::delete('/template-categories/{templateCategory}',[TemplateCategoryController::class, 'destroy'])->name('template-categories.destroy');

    // ─── Global Blacklist (admin only) ────────────────────────────────────────
    Route::get('/blacklist',                    [BlacklistController::class, 'index'])->name('blacklist.index');
    Route::delete('/blacklist/{blacklist}',     [BlacklistController::class, 'destroy'])->name('blacklist.destroy');

    // ─── User Management (admin only) ─────────────────────────────────────────
    Route::get('/users',                        [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/create',                 [UserManagementController::class, 'create'])->name('users.create');
    Route::post('/users',                       [UserManagementController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit',            [UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}',                 [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}',              [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');

    // ─── Audit Log (admin only) ───────────────────────────────────────────────
    Route::get('/audit-logs',        [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit.export');

    // ─── Reports (admin only) ─────────────────────────────────────────────────
    Route::get('/reports/costs',     [CostReportController::class, 'index'])->name('reports.costs');
    Route::get('/reports/countries', [CountryAnalyticsController::class, 'index'])->name('reports.countries');

    // ─── Profile ──────────────────────────────────────────────────────────────
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── React SPA ────────────────────────────────────────────────────────────────
// Catch all /spa/* routes and serve the built SPA index.html
Route::get('/spa/{any?}', function () {
    $path = public_path('spa/index.html');
    abort_unless(file_exists($path), 404, 'SPA not built. Run: cd frontend && npm run build');
    return response()->file($path);
})->where('any', '.*')->name('spa');

require __DIR__ . '/auth.php';
