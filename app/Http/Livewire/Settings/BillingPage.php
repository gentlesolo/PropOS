<?php

namespace App\Http\Livewire\Settings;

use App\Infrastructure\Payment\PaystackSubscriptionService;
use Livewire\Component;

class BillingPage extends Component
{
    public bool $isAnnual = false;
    
    public function toggleBillingCycle()
    {
        $this->isAnnual = !$this->isAnnual;
    }

    public function upgradeToPlan(string $planId)
    {
        $agency = auth()->user()->agency;
        $amount = config("pricing.plans.{$planId}." . ($this->isAnnual ? 'price_annual' : 'price_monthly'));
        
        if ($amount === 'custom') {
            $this->dispatch('notify', message: 'Please contact sales to upgrade to the Enterprise plan.', type: 'info');
            return;
        }

        try {
            $paystack = app(PaystackSubscriptionService::class);
            $link = $paystack->createCheckoutLink($agency, $amount, null, [
                'type' => 'subscription',
                'plan' => $planId,
                'cycle' => $this->isAnnual ? 'annual' : 'monthly',
            ]);
            
            return redirect()->away($link['url']);
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Unable to initialize payment. Please try again.', type: 'error');
        }
    }

    public function buyTopUp(string $topUpId)
    {
        $agency = auth()->user()->agency;
        $amount = config("pricing.top_ups.{$topUpId}.price");

        try {
            $paystack = app(PaystackSubscriptionService::class);
            $link = $paystack->createCheckoutLink($agency, $amount, null, [
                'type' => 'topup',
                'topup_id' => $topUpId,
                'credits' => config("pricing.top_ups.{$topUpId}.credits"),
            ]);
            
            return redirect()->away($link['url']);
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Unable to initialize payment. Please try again.', type: 'error');
        }
    }

    public function render()
    {
        $agency = auth()->user()->agency;
        return view('livewire.settings.billing-page', [
            'agency' => $agency,
            'currentPlan' => $agency->pricing_plan,
            'plans' => config('pricing.plans'),
            'topUps' => config('pricing.top_ups'),
        ])->layout('layouts.app');
    }
}
