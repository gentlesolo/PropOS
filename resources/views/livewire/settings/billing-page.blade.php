<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-text-primary tracking-tight sm:text-4xl">Pricing & Billing</h2>
        <p class="mt-4 text-xl text-text-secondary">Manage your subscription, team seats, and AI credits.</p>
    </div>

    <!-- Current Plan & Credits Summary -->
    <div class="bg-surface-card border border-border-default rounded-2xl p-6 mb-12 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-lg font-medium text-text-primary mb-2">Current Subscription</h3>
                <div class="flex items-center space-x-3 mb-4">
                    <span class="px-3 py-1 bg-brand-primary/10 text-brand-primary rounded-full text-sm font-semibold border border-brand-primary/30">
                        {{ $currentPlan['name'] }}
                    </span>
                    <span class="text-text-secondary text-sm capitalize">{{ $agency->billing_cycle }} billing</span>
                </div>
                <p class="text-text-secondary text-sm">
                    Your plan allows up to {{ $currentPlan['features']['max_agents'] === -1 ? 'unlimited' : $currentPlan['features']['max_agents'] }} agents. 
                    Currently using {{ $agency->users()->count() }} seat(s).
                </p>
            </div>
            
            <div class="bg-surface-sunken p-4 rounded-xl border border-border-default">
                <div class="flex justify-between items-end mb-2">
                    <h3 class="text-lg font-medium text-text-primary">AI Credits</h3>
                    <span class="text-sm text-text-secondary font-mono">{{ $agency->ai_credits_balance }} / {{ $agency->ai_credits_allocated_monthly }}</span>
                </div>
                
                @php
                    $percentage = $agency->ai_credits_allocated_monthly > 0 ? min(100, max(0, ($agency->ai_credits_balance / $agency->ai_credits_allocated_monthly) * 100)) : 100;
                    $colorClass = $percentage < 20 ? 'bg-danger-500' : 'bg-success-500';
                @endphp
                
                <div class="w-full bg-surface-page rounded-full h-2.5 mb-4 border border-border-default">
                    <div class="{{ $colorClass }} h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                </div>
                
                @if($percentage < 20)
                <div class="text-xs text-danger-600 mb-3 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    You are running low on AI credits!
                </div>
                @endif
                
                <p class="text-xs text-text-secondary">Resets on the 1st of every month. Unused credits do not roll over.</p>
            </div>
        </div>
    </div>

    <!-- AI Top-up Modal trigger area (could be a separate modal component) -->
    <div class="mb-12">
        <h3 class="text-2xl font-bold text-text-primary mb-6">AI Credit Top-ups</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($topUps as $id => $pack)
            <div class="bg-surface-card border border-border-default rounded-xl p-6 hover:border-brand-primary/50 transition duration-300">
                <h4 class="text-lg font-semibold text-text-primary">{{ $pack['name'] }}</h4>
                <div class="my-4 flex items-baseline text-3xl font-extrabold text-text-primary">
                    R{{ number_format($pack['price']) }}
                </div>
                <p class="text-text-secondary text-sm mb-6">+{{ number_format($pack['credits']) }} AI Credits</p>
                <button wire:click="buyTopUp('{{ $id }}')" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 px-4 rounded-lg text-sm font-medium text-white bg-gradient-to-br from-brand-primary to-brand-primary/80 shadow-brand-sm ring-1 ring-white/10 hover:bg-brand-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-primary transition" wire:loading.attr="disabled" wire:target="buyTopUp">
                <span wire:loading.remove wire:target="buyTopUp">Buy Top-up</span>
                <span wire:loading wire:target="buyTopUp" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Plan Toggle -->
    <div class="flex justify-center items-center space-x-4 mb-10">
        <span class="text-sm font-medium {{ !$isAnnual ? 'text-text-primary' : 'text-text-secondary' }}">Monthly</span>
        <button wire:click="toggleBillingCycle" type="button" class="disabled:opacity-70 disabled:cursor-not-allowed relative relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-primary bg-brand-primary" role="switch" aria-checked="{{ $isAnnual ? 'true' : 'false' }}" wire:loading.attr="disabled" wire:target="toggleBillingCycle">
                <span wire:loading.remove wire:target="toggleBillingCycle"><span class="sr-only">Toggle Annual Billing</span>
            <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 {{ $isAnnual ? 'translate-x-5' : 'translate-x-0' }}"></span></span>
                <span wire:loading wire:target="toggleBillingCycle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        <span class="text-sm font-medium {{ $isAnnual ? 'text-text-primary' : 'text-text-secondary' }}">Annually <span class="text-success-600 text-xs ml-1">(2 months free)</span></span>
    </div>

    <!-- Subscription Plans -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @foreach($plans as $id => $plan)
        <div class="bg-surface-card rounded-2xl border flex flex-col relative overflow-hidden transition-all duration-300 {{ $agency->subscription_plan === $id ? 'border-brand-primary shadow-brand-sm scale-105 z-10' : 'border-border-default hover:border-border-strong' }}">
            
            @if($agency->subscription_plan === $id)
            <div class="absolute top-0 right-0 -mr-1 -mt-1 w-24 h-24 overflow-hidden">
                <div class="absolute transform rotate-45 bg-brand-primary text-center text-white font-semibold py-1 left-[-34px] top-[32px] w-[170px] shadow-sm text-xs">
                    Current
                </div>
            </div>
            @endif
            
            <div class="p-8 flex-1">
                <h3 class="text-2xl font-bold text-text-primary mb-2">{{ $plan['name'] }}</h3>
                <p class="text-sm text-text-secondary mb-6 min-h-[40px]">{{ $plan['job'] }}</p>
                
                <div class="mb-6">
                    @if($plan['price_monthly'] === 'custom')
                        <span class="text-4xl font-extrabold text-text-primary">Custom</span>
                    @else
                        <span class="text-4xl font-extrabold text-text-primary">R{{ number_format($isAnnual ? $plan['price_annual'] : $plan['price_monthly']) }}</span>
                        <span class="text-text-secondary text-base font-medium">/{{ $isAnnual ? 'year' : 'month' }}</span>
                    @endif
                </div>

                <ul class="space-y-4 mb-8 text-sm text-text-secondary">
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-brand-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $plan['features']['max_agents'] === -1 ? 'Unlimited' : 'Up to ' . $plan['features']['max_agents'] }} Agents
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-brand-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $plan['features']['max_listings'] === -1 ? 'Unlimited' : 'Up to ' . $plan['features']['max_listings'] }} Active Listings
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-brand-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $plan['ai_credits_monthly'] === -1 ? 'Custom' : number_format($plan['ai_credits_monthly']) }} AI Credits / month
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-brand-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ ucfirst($plan['features']['ai_brief']) }} AI Daily Briefs
                    </li>
                </ul>
            </div>
            
            <div class="p-8 bg-surface-sunken border-t border-border-default">
                @if($agency->subscription_plan === $id)
                    <button disabled class="w-full py-3 px-4 border border-border-default rounded-lg text-sm font-medium text-text-secondary bg-surface-page cursor-not-allowed">
                        Current Plan
                    </button>
                @else
                    <button wire:click="upgradeToPlan('{{ $id }}')" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-3 px-4 rounded-lg text-sm font-medium transition {{ $id === 'agency_pro' ? 'bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 hover:bg-brand-secondary focus:ring-2 focus:ring-offset-2 focus:ring-brand-primary' : 'bg-surface-page border border-border-default text-text-primary hover:bg-surface-hover focus:ring-2 focus:ring-offset-2 focus:ring-brand-primary' }}" wire:loading.attr="disabled" wire:target="upgradeToPlan">
                <span wire:loading.remove wire:target="upgradeToPlan">{{ $id === 'enterprise' ? 'Contact Sales' : 'Upgrade to ' . $plan['name'] }}</span>
                <span wire:loading wire:target="upgradeToPlan" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
