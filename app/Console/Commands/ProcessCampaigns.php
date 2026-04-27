<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Services\CampaignSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ProcessCampaigns extends Command
{
    protected $signature   = 'campaigns:process {--limit=1 : Max recipients to process per run}';
    protected $description = 'Process pending campaign recipients whose scheduled_at is due';

    public function handle(CampaignSender $sender): int
    {
        // Heartbeat: record that cron / scheduler reached this command
        Cache::put('cron.campaigns.last_run_at', now()->toIso8601String(), now()->addDay());

        $limit = max(1, (int) $this->option('limit'));

        $recipients = CampaignRecipient::query()
            ->whereHas('campaign', fn($q) => $q->where('status', 'running'))
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        if ($recipients->isEmpty()) {
            $this->info('No campaign recipients due.');
            $this->finalizeCompletedCampaigns();
            return 0;
        }

        foreach ($recipients as $r) {
            $this->info("Processing recipient #{$r->id} (campaign #{$r->campaign_id}, speaker #{$r->speaker_id})");
            $sender->processRecipient($r);
        }

        $this->finalizeCompletedCampaigns();
        return 0;
    }

    private function finalizeCompletedCampaigns(): void
    {
        $running = Campaign::where('status', 'running')->get();
        foreach ($running as $c) {
            $hasPending = $c->recipients()->whereIn('status', ['pending', 'processing'])->exists();
            if (!$hasPending) {
                $c->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);
                $this->info("Campaign #{$c->id} ({$c->name}) completed.");
            }
        }
    }
}
