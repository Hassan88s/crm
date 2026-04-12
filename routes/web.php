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

Route::get('/', function () {
    return view('welcome');
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
    Route::get('speakers/import', [SpeakerController::class, 'importForm'])->name('speakers.import');
    Route::post('speakers/import', [SpeakerController::class, 'importCsv'])->name('speakers.import.post');
    Route::get('speakers/{speaker}/edit', [SpeakerController::class, 'edit'])->name('speakers.edit');
    Route::put('speakers/{speaker}', [SpeakerController::class, 'update'])->name('speakers.update');
    Route::delete('speakers/{speaker}', [SpeakerController::class, 'destroy'])->name('speakers.destroy');
    Route::post('speakers/{speaker}/verify', [SpeakerController::class, 'verifyProfile'])->name('speakers.verify');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
    Route::post('settings/smtp', [SettingsController::class, 'updateSmtp'])->name('settings.smtp');
    Route::post('settings/smtp/test', [SettingsController::class, 'testSmtp'])->name('settings.smtp.test');
    Route::post('settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email');
    Route::post('settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    // Run Emails
    Route::get('emails', [EmailController::class, 'index'])->name('emails.index');
    Route::post('emails/send', [EmailController::class, 'send'])->name('emails.send');
    Route::post('emails/ai-draft', [EmailController::class, 'aiDraft'])->name('emails.ai_draft');

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

    // System Requirements
    Route::get('requirements', [RequirementsController::class, 'index'])->name('requirements');

    // IMAP settings
    Route::post('settings/imap', [SettingsController::class, 'updateImap'])->name('settings.imap');
    Route::post('settings/imap/test', [SettingsController::class, 'testImap'])->name('settings.imap.test');

    // Classified Replies
    Route::get('replies', [ReplyController::class, 'index'])->name('replies.index');
    Route::get('replies/{reply}', [ReplyController::class, 'show'])->name('replies.show');
    Route::post('replies/fetch', [ReplyController::class, 'fetch'])->name('replies.fetch');
    Route::post('replies/{reply}/reclassify', [ReplyController::class, 'reclassify'])->name('replies.reclassify');
    Route::post('replies/{reply}/send-reply', [ReplyController::class, 'sendReply'])->name('replies.sendReply');
});
