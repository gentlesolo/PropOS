<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-white sm:text-4xl">Pricing & Billing</h2>
        <p class="mt-4 text-xl text-zinc-400">Manage your subscription, team seats, and AI credits.</p>
    </div>

    <!-- Current Plan & Credits Summary -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 mb-12 shadow-xl glassmorphism">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-lg font-medium text-white mb-2">Current Subscription</h3>
                <div class="flex items-center space-x-3 mb-4">
                    <span class="px-3 py-1 bg-indigo-500/20 text-indigo-400 rounded-full text-sm font-semibold border border-indigo-500/30">
                        {{ $currentPlan['name'] }}
                    </span>
                    <span class="text-zinc-400 text-sm capitalize">{{ $agency->billing_cycle }} billing</span>
                </div>
                <p class="text-zinc-300 text-sm">
                    Your plan allows up to {{ $currentPlan['features']['max_agents'] === -1 ? 'unlimited' : $currentPlan['features']['max_agents'] }} agents. 
                    Currently using {{ $agency->users()->count() }} seat(s).
                </p>
            </div>
            
            <div class="bg-zinc-800/50 p-4 rounded-xl border border-zinc-700/50">
                <div class="flex justify-between items-end mb-2">
                    <h3 class="text-lg font-medium text-white">AI Credits</h3>
                    <span class="text-sm text-zinc-400 font-mono">{{ $agency->ai_credits_balance }} / {{ $agency->ai_credits_allocated_monthly }}</span>
                </div>
                
                @php
                    $percentage = $agency->ai_credits_allocated_monthly > 0 ? min(100, max(0, ($agency->ai_credits_balance / $agency->ai_credits_allocated_monthly) * 100)) : 100;
                    $colorClass = $percentage < 20 ? 'bg-red-500' : 'bg-emerald-500';
                @endphp
                
                <div class="w-full bg-zinc-700 rounded-full h-2.5 mb-4">
                    <div class="{{ $colorClass }} h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                </div>
                
                @if($percentage < 20)
                <div class="text-xs text-red-400 mb-3 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    You are running low on AI credits!
                </div>
                @endif
                
                <p class="text-xs text-zinc-400">Resets on the 1st of every month. Unused credits do not roll over.</p>
            </div>
        </div>
    </div>

    <!-- AI Top-up Modal trigger area (could be a separate modal component) -->
    <div class="mb-12">
        <h3 class="text-2xl font-bold text-white mb-6">AI Credit Top-ups</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($topUps as $id => $pack)
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 hover:border-indigo-500/50 transition duration-300">
                <h4 class="text-lg font-semibold text-white">{{ $pack['name'] }}</h4>
                <div class="my-4 flex items-baseline text-3xl font-extrabold text-white">
                    R{{ number_format($pack['price']) }}
                </div>
                <p class="text-zinc-400 text-sm mb-6">+{{ number_format($pack['credits']) }} AI Credits</p>
                <button wire:click="buyTopUp('{{ $id }}')" class="w-full py-2 px-4 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-zinc-900 transition shadow-lg shadow-indigo-500/30">
                    Buy Top-up
                </button>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Plan Toggle -->
    <div class="flex justify-center items-center space-x-4 mb-10">
        <span class="text-sm font-medium {{ !$isAnnual ? 'text-white' : 'text-zinc-400' }}">Monthly</span>
        <button wire:click="toggleBillingCycle" type="button" class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 bg-indigo-600" role="switch" aria-checked="{{ $isAnnual ? 'true' : 'false' }}">
            <span class="sr-only">Toggle Annual Billing</span>
            <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 {{ $isAnnual ? 'translate-x-5' : 'translate-x-0' }}"></span>
        </button>
        <span class="text-sm font-medium {{ $isAnnual ? 'text-white' : 'text-zinc-400' }}">Annually <span class="text-emerald-400 text-xs ml-1">(2 months free)</span></span>
    </div>

    <!-- Subscription Plans -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @foreach($plans as $id => $plan)
        <div class="bg-zinc-900 rounded-2xl border flex flex-col relative overflow-hidden transition-all duration-300 {{ $agency->subscription_plan === $id ? 'border-indigo-500 shadow-xl shadow-indigo-500/20 scale-105 z-10' : 'border-zinc-800 hover:border-zinc-600' }}">
            
            @if($agency->subscription_plan === $id)
            <div class="absolute top-0 right-0 -mr-1 -mt-1 w-24 h-24 overflow-hidden">
                <div class="absolute transform rotate-45 bg-indigo-600 text-center text-white font-semibold py-1 left-[-34px] top-[32px] w-[170px] shadow-sm text-xs">
                    Current
                </div>
            </div>
            @endif
            
            <div class="p-8 flex-1">
                <h3 class="text-2xl font-bold text-white mb-2">{{ $plan['name'] }}</h3>
                <p class="text-sm text-zinc-400 mb-6 min-h-[40px]">{{ $plan['job'] }}</p>
                
                <div class="mb-6">
                    @if($plan['price_monthly'] === 'custom')
                        <span class="text-4xl font-extrabold text-white">Custom</span>
                    @else
                        <span class="text-4xl font-extrabold text-white">R{{ number_format($isAnnual ? $plan['price_annual'] : $plan['price_monthly']) }}</span>
                        <span class="text-zinc-400 text-base font-medium">/{{ $isAnnual ? 'year' : 'month' }}</span>
                    @endif
                </div>

                <ul class="space-y-4 mb-8 text-sm text-zinc-300">
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $plan['features']['max_agents'] === -1 ? 'Unlimited' : 'Up to ' . $plan['features']['max_agents'] }} Agents
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $plan['features']['max_listings'] === -1 ? 'Unlimited' : 'Up to ' . $plan['features']['max_listings'] }} Active Listings
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $plan['ai_credits_monthly'] === -1 ? 'Custom' : number_format($plan['ai_credits_monthly']) }} AI Credits / month
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ ucfirst($plan['features']['ai_brief']) }} AI Daily Briefs
                    </li>
                </ul>
            </div>
            
            <div class="p-8 bg-zinc-800/30 border-t border-zinc-800">
                @if($agency->subscription_plan === $id)
                    <button disabled class="w-full py-3 px-4 border border-zinc-700 rounded-lg text-sm font-medium text-zinc-400 bg-zinc-800/50 cursor-not-allowed">
                        Current Plan
                    </button>
                @else
                    <button wire:click="upgradeToPlan('{{ $id }}')" class="w-full py-3 px-4 border border-transparent rounded-lg text-sm font-medium text-white {{ $id === 'agency_pro' ? 'bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30' : 'bg-zinc-700 hover:bg-zinc-600' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-zinc-900 transition">
                        {{ $id === 'enterprise' ? 'Contact Sales' : 'Upgrade to ' . $plan['name'] }}
                    </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
