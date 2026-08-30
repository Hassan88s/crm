<?php

namespace App\Http\Controllers\Api;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailLog;
use App\Models\EmailReply;
use App\Models\Event;
use App\Models\Speaker;
use Illuminate\Http\Request;

class StatsController extends ApiController
{
    public function index(Request $request)
    {
        return $this->ok([
            'events'        => Event::count(),
            'speakers'      => Speaker::count(),
            'emails_sent'   => EmailLog::where('status', 'sent')->count(),
            'emails_failed' => EmailLog::where('status', 'failed')->count(),
            'replies'       => EmailReply::count(),
            'campaigns' => [
                'total'     => Campaign::count(),
                'running'   => Campaign::where('status', 'running')->count(),
                'paused'    => Campaign::where('status', 'paused')->count(),
                'completed' => Campaign::where('status', 'completed')->count(),
            ],
            'recipients_by_status' => CampaignRecipient::selectRaw('status, COUNT(*) as c')
                ->groupBy('status')->pluck('c', 'status'),
            'replies_by_category' => EmailReply::selectRaw('category, COUNT(*) as c')
                ->groupBy('category')->pluck('c', 'category'),
            'now' => now()->toIso8601String(),
        ]);
    }
}
