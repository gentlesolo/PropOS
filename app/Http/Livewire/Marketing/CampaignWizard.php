<?php

namespace App\Http\Livewire\Marketing;

use Livewire\Component;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\CampaignContent;
use App\Domain\AI\Contracts\AiCompletionServiceInterface;

class CampaignWizard extends Component
{
    public $step = 1;
    
    // Step 1: Listing
    public $selectedListingId = null;
    
    // Step 2: Goal
    public $goal = 'maximise_inquiries';
    
    // Step 3: Channels
    public $channels = [
        'instagram' => true,
        'facebook' => false,
        'linkedin' => false,
        'email' => false,
        'whatsapp' => false,
    ];
    
    // Step 4 & 5: Generated Content
    public $generatedContents = [];
    public $isGenerating = false;

    public function nextStep()
    {
        if ($this->step === 3) {
            $this->generateContent();
        } else {
            $this->step++;
        }
    }

    public function prevStep()
    {
        $this->step--;
    }

    public function selectListing($id)
    {
        $this->selectedListingId = $id;
        $this->nextStep();
    }

    public function generateContent()
    {
        $this->isGenerating = true;
        $this->step = 4;
        
        // This gives Livewire time to render the loading state
        // In a real app we'd dispatch a job and poll, but for UI fidelity we simulate the delay.
        // We will call completeGeneration() right after rendering.
    }

    public function completeGeneration(AiCompletionServiceInterface $aiService)
    {
        $listing = Listing::with('property')->find($this->selectedListingId);
        $address = $listing->property->address_line_1 . ', ' . $listing->property->city;
        $price = '₦' . number_format($listing->listing_price);

        $selectedChannels = array_keys(array_filter($this->channels));

        foreach ($selectedChannels as $channel) {
            $systemPrompt = "You are an expert real estate copywriter. Write a highly engaging marketing post for $channel.";
            $userPrompt = "Create a post for a property located at $address. Price: $price. Goal: {$this->goal}. Keep it concise, engaging, and use appropriate emojis and formatting for $channel.";
            
            $content = $aiService->generate($systemPrompt, $userPrompt, ['temperature' => 0.8]);
            
            $this->generatedContents[$channel] = $content;
        }

        $this->isGenerating = false;
        $this->step = 5;
    }

    public function saveCampaign()
    {
        $campaign = Campaign::create([
            'agency_id' => auth()->user()->agency_id,
            'listing_id' => $this->selectedListingId,
            'user_id' => auth()->id(),
            'name' => 'Campaign: ' . Listing::find($this->selectedListingId)->property->address_line_1,
            'goal' => $this->goal,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(1),
        ]);

        foreach ($this->generatedContents as $channel => $body) {
            CampaignContent::create([
                'campaign_id' => $campaign->id,
                'channel' => $channel,
                'content_body' => $body,
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(1),
            ]);
        }

        $this->dispatch('notify', message: 'Campaign scheduled successfully!', type: 'success');
        return redirect()->route('dashboard');
    }

    public function render()
    {
        $listings = Listing::with('property')->where('agency_id', auth()->user()->agency_id)->get();
        return view('livewire.marketing.campaign-wizard', [
            'listings' => $listings,
            'selectedListing' => $this->selectedListingId ? Listing::with('property')->find($this->selectedListingId) : null,
        ])->layout('layouts.app');
    }
}
