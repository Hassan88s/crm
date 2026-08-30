<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SpeakerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\ScraperController;
use App\Http\Controllers\RequirementsController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\SmtpAccountController;
use App\Http\Controllers\ImapAccountController;
use App\Http\Controllers\CampaignController;

Route::get('/', function () {
    return redirect('/admin/login');
});

// ── Deployment route (run migrations, clear cache, link storage) ─────────
Route::get('/deploy/{token}', function ($token) {
    if ($token !== env('DEPLOY_TOKEN', 'pulsecore-deploy-2026')) {
        abort(403, 'Invalid deploy token.');
    }

    $output = [];

    // Run migrations
    \Artisan::call('migrate', ['--force' => true]);
    $output[] = '✓ Migrations: ' . trim(\Artisan::output());

    // Clear caches
    \Artisan::call('config:clear');
    $output[] = '✓ Config cache cleared';

    \Artisan::call('route:clear');
    $output[] = '✓ Route cache cleared';

    \Artisan::call('view:clear');
    $output[] = '✓ View cache cleared';

    // Storage link
    try {
        \Artisan::call('storage:link');
        $output[] = '✓ Storage linked';
    } catch (\Exception $e) {
        $output[] = '⚠ Storage link: ' . $e->getMessage();
    }

    return '<pre>' . implode("\n", $output) . '</pre>';
});

// Admin auth routes
Route::get('admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin protected routes
Route::middleware([App\Http\Middleware\AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [AdminAuthController::class, 'dashboard'])->name('dashboard');
    Route::get('customers', [AdminAuthController::class, 'customers'])->name('customers');
    Route::get('leads', [AdminAuthController::class, 'leads'])->name('leads');
    Route::get('deals', [AdminAuthController::class, 'deals'])->name('deals');
    // Events CRUD
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
    Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    // Speakers CRUD + CSV import
    Route::get('speakers', [SpeakerController::class, 'index'])->name('speakers.index');
    Route::get('speakers/create', [SpeakerController::class, 'create'])->name('speakers.create');
    Route::post('speakers', [SpeakerController::class, 'store'])->name('speakers.store');
    Route::delete('speakers', [SpeakerController::class, 'destroyAll'])->name('speakers.destroyAll');
    Route::delete('speakers/by-event/{event}', [SpeakerController::class, 'destroyByEvent'])->name('speakers.destroyByEvent');
    Route::get('speakers/export', [SpeakerController::class, 'export'])->name('speakers.export');
    Route::get('speakers/import', [SpeakerController::class, 'importForm'])->name('speakers.import');
    Route::post('speakers/import/preview', [SpeakerController::class, 'importPreview'])->name('speakers.import.preview');
    Route::post('speakers/import', [SpeakerController::class, 'importCsv'])->name('speakers.import.post');
    Route::get('speakers/{speaker}/edit', [SpeakerController::class, 'edit'])->name('speakers.edit');
    Route::put('speakers/{speaker}', [SpeakerController::class, 'update'])->name('speakers.update');
    Route::delete('speakers/{speaker}', [SpeakerController::class, 'destroy'])->name('speakers.destroy');
    Route::post('speakers/{speaker}/verify', [SpeakerController::class, 'verifyProfile'])->name('speakers.verify');
    Route::post('speakers/{speaker}/find-linkedin', [SpeakerController::class, 'findLinkedIn'])->name('speakers.findLinkedIn');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
    Route::post('settings/smtp', [SettingsController::class, 'updateSmtp'])->name('settings.smtp');
    Route::post('settings/smtp/test', [SettingsController::class, 'testSmtp'])->name('settings.smtp.test');
    Route::post('settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email');
    Route::post('settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    // Run Emails
    // AI-powered Campaigns (replaces the old Send Invites flow)
    Route::get('emails',                       [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('emails/create',                [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('emails',                      [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('emails/create-manual',         [CampaignController::class, 'createManual'])->name('campaigns.createManual');
    Route::post('emails/manual',               [CampaignController::class, 'storeManual'])->name('campaigns.storeManual');
    Route::get('emails/{campaign}',            [CampaignController::class, 'show'])->name('campaigns.show');
    Route::post('emails/{campaign}/start',     [CampaignController::class, 'start'])->name('campaigns.start');
    Route::post('emails/{campaign}/toggle-attach', [CampaignController::class, 'toggleAttach'])->name('campaigns.toggleAttach');
    Route::post('emails/{campaign}/pause',     [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('emails/{campaign}/resume',    [CampaignController::class, 'resume'])->name('campaigns.resume');
    Route::delete('emails/{campaign}',         [CampaignController::class, 'destroy'])->name('campaigns.destroy');
    Route::post('emails/{campaign}/preview/{speaker}', [CampaignController::class, 'previewRecipient'])->name('campaigns.preview');
    Route::get('emails/{campaign}/recipient/{recipient}/email', [CampaignController::class, 'recipientEmail'])->name('campaigns.recipientEmail');
    Route::post('emails/{campaign}/recipient/{recipient}/resend', [CampaignController::class, 'resendOne'])->name('campaigns.resendOne');
    Route::post('emails/{campaign}/resend-failed', [CampaignController::class, 'resendFailed'])->name('campaigns.resendFailed');

    // Backward-compatible alias for the old "Send Invites" link/name (some places may still reference it)
    Route::get('emails/legacy/index',          [CampaignController::class, 'index'])->name('emails.index');

    // SMTP Accounts
    Route::get('smtp-accounts', [SmtpAccountController::class, 'index'])->name('smtp-accounts.index');
    Route::get('smtp-accounts/create', [SmtpAccountController::class, 'create'])->name('smtp-accounts.create');
    Route::post('smtp-accounts', [SmtpAccountController::class, 'store'])->name('smtp-accounts.store');
    Route::get('smtp-accounts/{smtp_account}/edit', [SmtpAccountController::class, 'edit'])->name('smtp-accounts.edit');
    Route::put('smtp-accounts/{smtp_account}', [SmtpAccountController::class, 'update'])->name('smtp-accounts.update');
    Route::delete('smtp-accounts/{smtp_account}', [SmtpAccountController::class, 'destroy'])->name('smtp-accounts.destroy');
    Route::post('smtp-accounts/{smtp_account}/toggle', [SmtpAccountController::class, 'toggle'])->name('smtp-accounts.toggle');
    Route::post('smtp-accounts/{smtp_account}/test', [SmtpAccountController::class, 'test'])->name('smtp-accounts.test');

    // IMAP Accounts
    Route::get('imap-accounts', [ImapAccountController::class, 'index'])->name('imap-accounts.index');
    Route::get('imap-accounts/create', [ImapAccountController::class, 'create'])->name('imap-accounts.create');
    Route::post('imap-accounts', [ImapAccountController::class, 'store'])->name('imap-accounts.store');
    Route::get('imap-accounts/{imap_account}/edit', [ImapAccountController::class, 'edit'])->name('imap-accounts.edit');
    Route::put('imap-accounts/{imap_account}', [ImapAccountController::class, 'update'])->name('imap-accounts.update');
    Route::delete('imap-accounts/{imap_account}', [ImapAccountController::class, 'destroy'])->name('imap-accounts.destroy');
    Route::post('imap-accounts/{imap_account}/toggle', [ImapAccountController::class, 'toggle'])->name('imap-accounts.toggle');
    Route::post('imap-accounts/{imap_account}/test', [ImapAccountController::class, 'test'])->name('imap-accounts.test');

    // Inbox (IMAP)
    Route::get('inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('inbox/contacts', [InboxController::class, 'contacts'])->name('inbox.contacts');
    Route::get('inbox/{uid}', [InboxController::class, 'show'])->name('inbox.show');
    Route::post('inbox/{uid}/read', [InboxController::class, 'markRead'])->name('inbox.markRead');
    Route::post('inbox/{uid}/move', [InboxController::class, 'move'])->name('inbox.move');
    Route::delete('inbox/{uid}', [InboxController::class, 'destroy'])->name('inbox.destroy');

    // Scraper
    Route::get('scraper', [ScraperController::class, 'index'])->name('scraper.index');
    Route::post('scraper/scrape', [ScraperController::class, 'scrape'])->name('scraper.scrape');
    Route::post('scraper/import', [ScraperController::class, 'import'])->name('scraper.import');
    Route::post('scraper/discover', [ScraperController::class, 'discover'])->name('scraper.discover');

    // Settings - OpenAI
    Route::post('settings/openai', [SettingsController::class, 'updateOpenAI'])->name('settings.openai');
    Route::get('settings/openai/usage', [SettingsController::class, 'checkOpenAIUsage'])->name('settings.openai.usage');

    // Settings - API Keys (for external agents to call /api/v1/*)
    Route::post  ('settings/api-keys',              [SettingsController::class, 'storeApiKey'])->name('settings.apiKeys.store');
    Route::post  ('settings/api-keys/{apiKey}/revoke', [SettingsController::class, 'revokeApiKey'])->name('settings.apiKeys.revoke');
    Route::delete('settings/api-keys/{apiKey}',     [SettingsController::class, 'destroyApiKey'])->name('settings.apiKeys.destroy');

    // System Requirements
    Route::get('requirements', [RequirementsController::class, 'index'])->name('requirements');

    // IMAP settings
    Route::post('settings/imap', [SettingsController::class, 'updateImap'])->name('settings.imap');
    Route::post('settings/imap/test', [SettingsController::class, 'testImap'])->name('settings.imap.test');

    // Classified Replies
    Route::get('replies', [ReplyController::class, 'index'])->name('replies.index');
    Route::get('replies/export', [ReplyController::class, 'export'])->name('replies.export');
    Route::post('replies/fetch', [ReplyController::class, 'fetch'])->name('replies.fetch');
    Route::get('replies/{reply}', [ReplyController::class, 'show'])->name('replies.show');
    Route::post('replies/{reply}/reclassify', [ReplyController::class, 'reclassify'])->name('replies.reclassify');
    Route::post('replies/{reply}/category', [ReplyController::class, 'changeCategory'])->name('replies.changeCategory');
    Route::post('replies/{reply}/send-reply', [ReplyController::class, 'sendReply'])->name('replies.sendReply');
    Route::delete('replies/{reply}', [ReplyController::class, 'destroy'])->name('replies.destroy');
    Route::delete('replies', [ReplyController::class, 'destroyAll'])->name('replies.destroyAll');
});
