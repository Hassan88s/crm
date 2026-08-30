<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api;

Route::get('/health', fn () => response()->json(['ok' => true, 'ts' => now()->toIso8601String()]));

Route::middleware('api.auth')->prefix('v1')->group(function () {

    // Meta
    Route::get('/me',    [Api\ApiKeyController::class, 'me']);
    Route::get('/stats', [Api\StatsController::class,   'index']);

    // Events
    Route::get   ('/events',                    [Api\EventController::class, 'index']);
    Route::post  ('/events',                    [Api\EventController::class, 'store']);
    Route::get   ('/events/{event}',            [Api\EventController::class, 'show']);
    Route::put   ('/events/{event}',            [Api\EventController::class, 'update']);
    Route::patch ('/events/{event}',            [Api\EventController::class, 'update']);
    Route::delete('/events/{event}',            [Api\EventController::class, 'destroy']);
    Route::get   ('/events/{event}/speakers',   [Api\EventController::class, 'speakers']);

    // Speakers
    Route::get   ('/speakers',                       [Api\SpeakerController::class, 'index']);
    Route::post  ('/speakers',                       [Api\SpeakerController::class, 'store']);
    Route::get   ('/speakers/{speaker}',             [Api\SpeakerController::class, 'show']);
    Route::put   ('/speakers/{speaker}',             [Api\SpeakerController::class, 'update']);
    Route::patch ('/speakers/{speaker}',             [Api\SpeakerController::class, 'update']);
    Route::delete('/speakers/{speaker}',             [Api\SpeakerController::class, 'destroy']);
    Route::post  ('/speakers/{speaker}/verify',      [Api\SpeakerController::class, 'verify']);
    Route::post  ('/speakers/{speaker}/find-linkedin',[Api\SpeakerController::class, 'findLinkedIn']);

    // Campaigns
    Route::get   ('/campaigns',                                     [Api\CampaignController::class, 'index']);
    Route::get   ('/campaigns/{campaign}',                          [Api\CampaignController::class, 'show']);
    Route::get   ('/campaigns/{campaign}/recipients',               [Api\CampaignController::class, 'recipients']);
    Route::post  ('/campaigns/{campaign}/start',                    [Api\CampaignController::class, 'start']);
    Route::post  ('/campaigns/{campaign}/pause',                    [Api\CampaignController::class, 'pause']);
    Route::post  ('/campaigns/{campaign}/resume',                   [Api\CampaignController::class, 'resume']);
    Route::post  ('/campaigns/{campaign}/resend-failed',            [Api\CampaignController::class, 'resendFailed']);
    Route::post  ('/campaigns/{campaign}/recipients/{recipient}/resend', [Api\CampaignController::class, 'resendOne']);
    Route::post  ('/campaigns/{campaign}/toggle-attach',            [Api\CampaignController::class, 'toggleAttach']);
    Route::delete('/campaigns/{campaign}',                          [Api\CampaignController::class, 'destroy']);

    // Email logs
    Route::get('/email-logs',        [Api\EmailLogController::class, 'index']);
    Route::get('/email-logs/{log}',  [Api\EmailLogController::class, 'show']);

    // Replies
    Route::get   ('/replies',                          [Api\ReplyController::class, 'index']);
    Route::post  ('/replies/fetch',                    [Api\ReplyController::class, 'fetch']);
    Route::get   ('/replies/{reply}',                  [Api\ReplyController::class, 'show']);
    Route::post  ('/replies/{reply}/category',         [Api\ReplyController::class, 'changeCategory']);
    Route::post  ('/replies/{reply}/reclassify',       [Api\ReplyController::class, 'reclassify']);
    Route::post  ('/replies/{reply}/send-reply',       [Api\ReplyController::class, 'sendReply']);
    Route::delete('/replies/{reply}',                  [Api\ReplyController::class, 'destroy']);

    // SMTP accounts
    Route::get   ('/smtp-accounts',                       [Api\SmtpAccountController::class, 'index']);
    Route::post  ('/smtp-accounts',                       [Api\SmtpAccountController::class, 'store']);
    Route::get   ('/smtp-accounts/{smtp_account}',        [Api\SmtpAccountController::class, 'show']);
    Route::put   ('/smtp-accounts/{smtp_account}',        [Api\SmtpAccountController::class, 'update']);
    Route::patch ('/smtp-accounts/{smtp_account}',        [Api\SmtpAccountController::class, 'update']);
    Route::delete('/smtp-accounts/{smtp_account}',        [Api\SmtpAccountController::class, 'destroy']);
    Route::post  ('/smtp-accounts/{smtp_account}/toggle', [Api\SmtpAccountController::class, 'toggle']);

    // IMAP accounts
    Route::get   ('/imap-accounts',                       [Api\ImapAccountController::class, 'index']);
    Route::post  ('/imap-accounts',                       [Api\ImapAccountController::class, 'store']);
    Route::get   ('/imap-accounts/{imap_account}',        [Api\ImapAccountController::class, 'show']);
    Route::put   ('/imap-accounts/{imap_account}',        [Api\ImapAccountController::class, 'update']);
    Route::patch ('/imap-accounts/{imap_account}',        [Api\ImapAccountController::class, 'update']);
    Route::delete('/imap-accounts/{imap_account}',        [Api\ImapAccountController::class, 'destroy']);
    Route::post  ('/imap-accounts/{imap_account}/toggle', [Api\ImapAccountController::class, 'toggle']);

    // Inbox
    Route::get   ('/inbox',              [Api\InboxController::class, 'index']);
    Route::get   ('/inbox/{uid}',        [Api\InboxController::class, 'show']);
    Route::post  ('/inbox/{uid}/read',   [Api\InboxController::class, 'markRead']);
    Route::post  ('/inbox/{uid}/move',   [Api\InboxController::class, 'move']);
    Route::delete('/inbox/{uid}',        [Api\InboxController::class, 'destroy']);

    // Scraper
    Route::post('/scraper/scrape',   [Api\ScraperController::class, 'scrape']);
    Route::post('/scraper/discover', [Api\ScraperController::class, 'discover']);
    Route::post('/scraper/import',   [Api\ScraperController::class, 'import']);
});
