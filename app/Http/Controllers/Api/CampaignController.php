<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\CampaignController as WebCampaignController;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Http\Request;

class CampaignController extends ApiController
{
    public function index(Request $request)
    {
        $q = Campaign::query()->withCount('recipients')->with('event:id,name');
        if ($s = $request->query('status')) $q->where('status', $s);
        $q->orderBy($request->query('sort', 'created_at'), $request->query('dir', 'desc'));
        return $this->paginate($q, $request);
    }

    public function show(Campaign $campaign)
    {
        $campaign->load('event');
        $counts = $campaign->recipients()
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return $this->ok([
            'campaign'         => $campaign,
            'recipient_counts' => $counts,
            'progress_percent' => $campaign->progressPercent(),
        ]);
    }

    public function recipients(Request $request, Campaign $campaign)
    {
        $q = $campaign->recipients()->with(['speaker:id,first_name,last_name,email', 'smtpAccount:id,name']);
        if ($s = $request->query('status')) $q->where('status', $s);
        $q->orderByRaw("FIELD(status, 'processing','pending','failed','skipped','sent')")
          ->orderBy('scheduled_at')->orderBy('id');
        return $this->paginate($q, $request);
    }

    public function start(Campaign $campaign, WebCampaignController $ctrl)
    {
        $ctrl->start($campaign);
        return $this->ok($campaign->fresh());
    }

    public function pause(Campaign $campaign, WebCampaignController $ctrl)
    {
        $ctrl->pause($campaign);
        return $this->ok($campaign->fresh());
    }

    public function resume(Campaign $campaign, WebCampaignController $ctrl)
    {
        $ctrl->resume($campaign);
        return $this->ok($campaign->fresh());
    }

    public function resendFailed(Campaign $campaign, WebCampaignController $ctrl)
    {
        $ctrl->resendFailed($campaign);
        return $this->ok($campaign->fresh());
    }

    public function resendOne(Campaign $campaign, CampaignRecipient $recipient, WebCampaignController $ctrl)
    {
        $ctrl->resendOne($campaign, $recipient);
        return $this->ok($recipient->fresh());
    }

    public function toggleAttach(Campaign $campaign, WebCampaignController $ctrl)
    {
        $ctrl->toggleAttach($campaign);
        return $this->ok($campaign->fresh());
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->recipients()->delete();
        $campaign->delete();
        return $this->ok(['deleted' => true]);
    }
}
