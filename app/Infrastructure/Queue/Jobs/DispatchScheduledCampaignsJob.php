<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\CampaignContent;
use App\Infrastructure\Persistence\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DispatchScheduledCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $campaigns = Campaign::with(['contents', 'listing.property'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {
            $this->dispatchCampaign($campaign);
        }
    }

    private function dispatchCampaign(Campaign $campaign): void
    {
        try {
            $campaign->update(['status' => 'sending']);

            foreach ($campaign->contents as $content) {
                if ($content->status !== 'scheduled') {
                    continue;
                }

                $this->dispatchContent($campaign, $content);
                $content->update(['status' => 'sent', 'sent_at' => now()]);
            }

            $campaign->update(['status' => 'sent']);
        } catch (\Exception $e) {
            Log::error('Campaign dispatch failed', [
                'campaign_id' => $campaign->id,
                'error'       => $e->getMessage(),
            ]);
            $campaign->update(['status' => 'failed']);
        }
    }

    private function dispatchContent(Campaign $campaign, CampaignContent $content): void
    {
        match ($content->channel) {
            'email'     => $this->sendEmailBlast($campaign, $content),
            'whatsapp'  => $this->queueWhatsAppBroadcast($campaign, $content),
            default     => Log::info("Campaign channel '{$content->channel}' dispatched", [
                'campaign_id' => $campaign->id,
                'content_id'  => $content->id,
            ]),
        };
    }

    private function sendEmailBlast(Campaign $campaign, CampaignContent $content): void
    {
        $agencyId  = $campaign->agency_id;
        $property  = $campaign->listing?->property;
        $subject   = $campaign->name . ($property ? ' — ' . $property->address_line_1 : '');

        // Send to all active contacts with email addresses for this agency
        $contacts = Contact::where('agency_id', $agencyId)
            ->whereNotNull('email')
            ->whereIn('status', ['active', 'qualified', 'nurturing'])
            ->limit(500)
            ->get(['id', 'first_name', 'last_name', 'email']);

        foreach ($contacts as $contact) {
            try {
                Mail::raw($content->content_body, function ($message) use ($contact, $subject) {
                    $message->to($contact->email, $contact->full_name)->subject($subject);
                });
            } catch (\Exception $e) {
                Log::warning('Campaign email failed for contact', [
                    'contact_id' => $contact->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    private function queueWhatsAppBroadcast(Campaign $campaign, CampaignContent $content): void
    {
        // WhatsApp broadcasts are queued as individual WhatsAppMessage records
        // and picked up by the WhatsApp API client dispatcher.
        $contacts = Contact::where('agency_id', $campaign->agency_id)
            ->whereNotNull('phone')
            ->whereIn('status', ['active', 'qualified'])
            ->limit(250)
            ->get(['id', 'phone']);

        foreach ($contacts as $contact) {
            \App\Infrastructure\Persistence\Models\WhatsAppMessage::create([
                'agency_id'   => $campaign->agency_id,
                'contact_id'  => $contact->id,
                'to_number'   => $contact->phone,
                'body'        => $content->content_body,
                'direction'   => 'outbound',
                'status'      => 'queued',
            ]);
        }
    }
}
