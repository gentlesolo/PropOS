<div x-data="{ step: @entangle('step'), invitation: @json(!!$invitationToken), billing_cycle: @entangle('billing_cycle'), subscription_plan: @entangle('subscription_plan') }" 
    x-init="$watch('step', val => {
        let progressBar = document.getElementById('auth-progress-bar');
        if (progressBar) {
            if (invitation) {
                progressBar.style.width = val === 2 ? '50%' : '100%';
            } else {
                progressBar.style.width = val === 1 ? '25%' : (val === 2 ? '50%' : (val === 3 ? '75%' : '100%'));
            }
        }
     }); 
     let progressBar = document.getElementById('auth-progress-bar');
     if (progressBar) {
         if (invitation) {
             progressBar.style.width = step === 2 ? '50%' : '100%';
         } else {
             progressBar.style.width = step === 1 ? '25%' : (step === 2 ? '50%' : (step === 3 ? '75%' : '100%'));
         }
     }"
     class="relative">

    <!-- Styles for validation slide down -->
    <style>
        @keyframes slide-down {
            0% { opacity: 0; transform: translateY(-4px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-down {
            animation: slide-down 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>

    <!-- Header Section -->
    <div class="mb-8">
        <!-- Mini mobile logo mark (visible on mobile only) -->
        <div class="lg:hidden flex items-center space-x-2.5 mb-6">
            <div class="h-8 w-8 rounded-md bg-[#10B981]/10 flex items-center justify-center border border-[#10B981]/25">
                <span class="font-bold text-base text-[#10B981]">P</span>
            </div>
            <span class="text-lg font-bold tracking-tight text-[#FAFAFA]">VillaCRM</span>
        </div>

        @if($invitationToken)
            <h2 class="text-2xl font-semibold tracking-tight text-[#FAFAFA] font-sans">Complete your profile</h2>
            <p class="mt-2 text-sm text-[#A1A1AA]">
                You have been invited to join <span class="text-[#FAFAFA] font-semibold">{{ $invitationAgencyName }}</span> as a <span class="text-[#10B981] font-semibold">{{ ucfirst($invitationRole) }}</span>.
            </p>
        @else
            <h2 class="text-2xl font-semibold tracking-tight text-[#FAFAFA] font-sans">Create your agency</h2>
            <p class="mt-2 text-sm text-[#A1A1AA]">
                Establish your command center for property operations.
            </p>
        @endif
    </div>

    <!-- Multi-Step Indicators -->
    @if(!$invitationToken)
        <div class="flex items-center space-x-4 mb-8 text-xs font-mono">
            <div class="flex items-center space-x-1.5">
                <span :class="step >= 1 ? 'bg-[#10B981] text-white border-[#10B981]' : 'bg-[#111827] text-[#52525B] border-white/5'" class="h-5 w-5 rounded-full flex items-center justify-center border text-[10px] font-bold transition-colors">1</span>
                <span :class="step >= 1 ? 'text-[#FAFAFA]' : 'text-[#52525B]'" class="font-semibold transition-colors">Agency</span>
            </div>
            <div class="h-px bg-white/5 flex-1"></div>
            <div class="flex items-center space-x-1.5">
                <span :class="step >= 2 ? 'bg-[#10B981] text-white border-[#10B981]' : 'bg-[#111827] text-[#52525B] border-white/5'" class="h-5 w-5 rounded-full flex items-center justify-center border text-[10px] font-bold transition-colors">2</span>
                <span :class="step >= 2 ? 'text-[#FAFAFA]' : 'text-[#52525B]'" class="font-semibold transition-colors">Owner</span>
            </div>
            <div class="h-px bg-white/5 flex-1"></div>
            <div class="flex items-center space-x-1.5">
                <span :class="step >= 3 ? 'bg-[#10B981] text-white border-[#10B981]' : 'bg-[#111827] text-[#52525B] border-white/5'" class="h-5 w-5 rounded-full flex items-center justify-center border text-[10px] font-bold transition-colors">3</span>
                <span :class="step >= 3 ? 'text-[#FAFAFA]' : 'text-[#52525B]'" class="font-semibold transition-colors">Plan</span>
            </div>
            <div class="h-px bg-white/5 flex-1"></div>
            <div class="flex items-center space-x-1.5">
                <span :class="step >= 4 ? 'bg-[#10B981] text-white border-[#10B981]' : 'bg-[#111827] text-[#52525B] border-white/5'" class="h-5 w-5 rounded-full flex items-center justify-center border text-[10px] font-bold transition-colors">4</span>
                <span :class="step >= 4 ? 'text-[#FAFAFA]' : 'text-[#52525B]'" class="font-semibold transition-colors">Security</span>
            </div>
        </div>
    @else
        <div class="flex items-center space-x-4 mb-8 text-xs font-mono">
            <div class="flex items-center space-x-1.5">
                <span :class="step >= 2 ? 'bg-[#10B981] text-white border-[#10B981]' : 'bg-[#111827] text-[#52525B] border-white/5'" class="h-5 w-5 rounded-full flex items-center justify-center border text-[10px] font-bold transition-colors">1</span>
                <span :class="step >= 2 ? 'text-[#FAFAFA]' : 'text-[#52525B]'" class="font-semibold transition-colors">Details</span>
            </div>
            <div class="h-px bg-white/5 flex-1"></div>
            <div class="flex items-center space-x-1.5">
                <span :class="step >= 3 ? 'bg-[#10B981] text-white border-[#10B981]' : 'bg-[#111827] text-[#52525B] border-white/5'" class="h-5 w-5 rounded-full flex items-center justify-center border text-[10px] font-bold transition-colors">2</span>
                <span :class="step >= 3 ? 'text-[#FAFAFA]' : 'text-[#52525B]'" class="font-semibold transition-colors">Password</span>
            </div>
        </div>
    @endif

    <!-- Sliding Steps Container -->
    <div class="relative w-full">

        <!-- STEP 1: Agency Details (Ignored in invitation mode) -->
        @if(!$invitationToken)
            <div x-show="step === 1"
                 x-transition:enter="transition ease-spring duration-300 transform"
                 x-transition:enter-start="translate-x-full opacity-0"
                 x-transition:enter-end="translate-x-0 opacity-100"
                 x-transition:leave="transition ease-spring duration-300 transform absolute top-0 left-0 w-full"
                 x-transition:leave-start="translate-x-0 opacity-100"
                 x-transition:leave-end="-translate-x-full opacity-0"
                 class="space-y-5">
                 
                <div>
                    <label for="agency_name" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Agency Name</label>
                    <input wire:model.live="agency_name" id="agency_name" type="text" required placeholder="Lagos Crest Realty"
                        class="w-full h-11 bg-[#111827] border @error('agency_name') border-[#F43F5E] @else border-white/10 @enderror text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                    @if($slug)
                        <div class="text-[11px] font-mono text-[#52525B] mt-1.5">
                            Workspace: <span class="text-[#10B981]">{{ $slug }}.villacrm.app</span>
                        </div>
                    @endif
                    @error('agency_name') 
                        <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                    @enderror
                    @error('slug') 
                        <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-2.5">Operating Country</label>
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Nigeria -->
                        <button type="button" wire:click="$set('country', 'NG')"
                            class="flex items-center space-x-3 p-3 border rounded-md text-sm transition-all focus:outline-none text-left cursor-pointer @if($country === 'NG') border-[#10B981] bg-[#10B981]/5 text-white @else border-white/10 bg-[#111827] text-[#A1A1AA] hover:border-white/20 @endif">
                            <span class="text-lg">🇳🇬</span>
                            <div>
                                <div class="font-semibold text-xs text-[#FAFAFA]">Nigeria</div>
                                <div class="text-[10px] text-[#A1A1AA] font-mono">NGN (₦)</div>
                            </div>
                        </button>
                        <!-- South Africa -->
                        <button type="button" wire:click="$set('country', 'ZA')"
                            class="flex items-center space-x-3 p-3 border rounded-md text-sm transition-all focus:outline-none text-left cursor-pointer @if($country === 'ZA') border-[#10B981] bg-[#10B981]/5 text-white @else border-white/10 bg-[#111827] text-[#A1A1AA] hover:border-white/20 @endif">
                            <span class="text-lg">🇿🇦</span>
                            <div>
                                <div class="font-semibold text-xs text-[#FAFAFA]">South Africa</div>
                                <div class="text-[10px] text-[#A1A1AA] font-mono">ZAR (R)</div>
                            </div>
                        </button>
                        <!-- Kenya -->
                        <button type="button" wire:click="$set('country', 'KE')"
                            class="flex items-center space-x-3 p-3 border rounded-md text-sm transition-all focus:outline-none text-left cursor-pointer @if($country === 'KE') border-[#10B981] bg-[#10B981]/5 text-white @else border-white/10 bg-[#111827] text-[#A1A1AA] hover:border-white/20 @endif">
                            <span class="text-lg">🇰🇪</span>
                            <div>
                                <div class="font-semibold text-xs text-[#FAFAFA]">Kenya</div>
                                <div class="text-[10px] text-[#A1A1AA] font-mono">KES (KSh)</div>
                            </div>
                        </button>
                        <!-- Ghana -->
                        <button type="button" wire:click="$set('country', 'GH')"
                            class="flex items-center space-x-3 p-3 border rounded-md text-sm transition-all focus:outline-none text-left cursor-pointer @if($country === 'GH') border-[#10B981] bg-[#10B981]/5 text-white @else border-white/10 bg-[#111827] text-[#A1A1AA] hover:border-white/20 @endif">
                            <span class="text-lg">🇬🇭</span>
                            <div>
                                <div class="font-semibold text-xs text-[#FAFAFA]">Ghana</div>
                                <div class="text-[10px] text-[#A1A1AA] font-mono">GHS (₵)</div>
                            </div>
                        </button>
                    </div>
                    @error('country') 
                        <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-2.5">Agency Size</label>
                    <div class="grid grid-cols-3 gap-2.5">
                        <button type="button" wire:click="$set('size', '1-5')"
                            class="h-10 text-xs font-semibold border rounded-md transition-all focus:outline-none cursor-pointer @if($size === '1-5') border-[#10B981] bg-[#10B981]/5 text-white @else border-white/10 bg-[#111827] text-[#A1A1AA] hover:border-white/20 @endif">
                            1-5 agents
                        </button>
                        <button type="button" wire:click="$set('size', '6-20')"
                            class="h-10 text-xs font-semibold border rounded-md transition-all focus:outline-none cursor-pointer @if($size === '6-20') border-[#10B981] bg-[#10B981]/5 text-white @else border-white/10 bg-[#111827] text-[#A1A1AA] hover:border-white/20 @endif">
                            6-20 agents
                        </button>
                        <button type="button" wire:click="$set('size', '21+')"
                            class="h-10 text-xs font-semibold border rounded-md transition-all focus:outline-none cursor-pointer @if($size === '21+') border-[#10B981] bg-[#10B981]/5 text-white @else border-white/10 bg-[#111827] text-[#A1A1AA] hover:border-white/20 @endif">
                            21+ agents
                        </button>
                    </div>
                    @error('size') 
                        <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="button" wire:click="nextStep" class="cta-shimmer w-full h-[44px] bg-[#10B981] text-white text-sm font-semibold rounded-md shadow-[0_2px_8px_rgba(16,185,129,0.16)] hover:bg-[#10B981]/90 active:scale-[0.98] transition-all flex items-center justify-center cursor-pointer">
                        Continue to profile
                    </button>
                </div>
            </div>
        @endif

        <!-- STEP 2: Your Details -->
        <div x-show="step === 2"
             x-transition:enter="transition ease-spring duration-300 transform"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-spring duration-300 transform absolute top-0 left-0 w-full"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="-translate-x-full opacity-0"
             class="space-y-5"
             style="display: none;">
             
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">First Name</label>
                    <input wire:model="first_name" id="first_name" type="text" required placeholder="Tunde"
                        class="w-full h-11 bg-[#111827] border @error('first_name') border-[#F43F5E] @else border-white/10 @enderror text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                    @error('first_name') 
                        <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                    @enderror
                </div>
                <div>
                    <label for="last_name" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Last Name</label>
                    <input wire:model="last_name" id="last_name" type="text" required placeholder="Adeniji"
                        class="w-full h-11 bg-[#111827] border @error('last_name') border-[#F43F5E] @else border-white/10 @enderror text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                    @error('last_name') 
                        <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                    @enderror
                </div>
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Email Address</label>
                <input wire:model="email" id="email" type="email" required placeholder="tunde@lagoscrest.com" @if($invitationToken) disabled @endif
                    class="w-full h-11 @if($invitationToken) bg-[#111827]/40 text-[#52525B] cursor-not-allowed border-white/5 @else bg-[#111827] text-[#FAFAFA] border-white/10 @endif border @error('email') border-[#F43F5E] @enderror text-sm placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                @error('email') 
                    <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Phone Number</label>
                <input wire:model="phone" id="phone" type="text" placeholder="+234 803 123 4567"
                    class="w-full h-11 bg-[#111827] border @error('phone') border-[#F43F5E] @else border-white/10 @enderror text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                @error('phone') 
                    <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                @enderror
            </div>

            <div>
                <label for="role" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Your Role</label>
                <select wire:model="role" id="role" @if($invitationToken) disabled @endif
                    class="w-full h-11 @if($invitationToken) bg-[#111827]/40 text-[#52525B] cursor-not-allowed border-white/5 @else bg-[#111827] text-[#FAFAFA] border-white/10 @endif border @error('role') border-[#F43F5E] @enderror text-sm px-3 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] transition-all duration-200">
                    <option value="principal" class="bg-[#090d16]">Principal / Broker-Owner</option>
                    <option value="agent" class="bg-[#090d16]">Real Estate Agent</option>
                    <option value="admin" class="bg-[#090d16]">Administrator</option>
                </select>
                @error('role') 
                    <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                @enderror
            </div>

            <div class="flex items-center space-x-3 pt-2">
                @if(!$invitationToken)
                    <button type="button" wire:click="previousStep" 
                        class="w-24 h-[44px] border border-white/10 hover:bg-white/5 text-[#A1A1AA] hover:text-[#FAFAFA] text-sm font-semibold rounded-md transition-all flex items-center justify-center cursor-pointer">
                        Back
                    </button>
                @endif
                <button type="button" wire:click="nextStep" 
                    class="cta-shimmer flex-1 h-[44px] bg-[#10B981] text-white text-sm font-semibold rounded-md shadow-[0_2px_8px_rgba(16,185,129,0.16)] hover:bg-[#10B981]/90 active:scale-[0.98] transition-all flex items-center justify-center cursor-pointer">
                    @if($invitationToken) Continue to security @else Choose your plan @endif
                </button>
            </div>
        </div>

        <!-- STEP 3: Plan Selection (Only for New Agencies) -->
        @if(!$invitationToken)
        <div x-show="step === 3"
             x-transition:enter="transition ease-spring duration-300 transform"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-spring duration-300 transform absolute top-0 left-0 w-full"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="-translate-x-full opacity-0"
             class="space-y-6"
             style="display: none;">

            <div class="text-center mb-6">
                <h3 class="text-lg font-bold text-white">Select your plan</h3>
                <p class="text-xs text-[#A1A1AA] mt-1">You will be redirected to securely complete your payment.</p>
            </div>

            <!-- Billing Cycle Toggle -->
            <div class="flex justify-center items-center space-x-4 mb-6">
                <span class="text-xs font-medium" :class="billing_cycle === 'monthly' ? 'text-white' : 'text-[#A1A1AA]'">Monthly</span>
                <button type="button" @click="billing_cycle = billing_cycle === 'monthly' ? 'annual' : 'monthly'" class="relative inline-flex flex-shrink-0 h-5 w-9 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 bg-[#10B981]">
                    <span class="sr-only">Toggle Annual Billing</span>
                    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200" :class="billing_cycle === 'annual' ? 'translate-x-4' : 'translate-x-0'"></span>
                </button>
                <span class="text-xs font-medium" :class="billing_cycle === 'annual' ? 'text-white' : 'text-[#A1A1AA]'">Annually <span class="text-[#10B981] ml-1">(-20%)</span></span>
            </div>

            <!-- Plan Cards -->
            <div class="flex flex-col space-y-3 max-h-[380px] overflow-y-auto pr-1">
                @foreach(config('pricing.plans') as $planKey => $plan)
                    <div @click="subscription_plan = '{{ $planKey }}'" class="cursor-pointer border rounded-xl p-4 transition-all relative"
                         :class="subscription_plan === '{{ $planKey }}' ? 'border-[#10B981] bg-[#10B981]/5 shadow-[0_0_15px_rgba(16,185,129,0.15)]' : 'border-white/10 bg-[#111827] hover:border-white/20'">
                        
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-sm font-bold text-white mb-0.5">{{ $plan['name'] }}</h4>
                                <p class="text-[10px] text-[#A1A1AA]">{{ $plan['job'] }}</p>
                            </div>
                            <div class="text-right">
                                @if($plan['price_monthly'] === 'custom')
                                    <div class="text-lg font-extrabold text-white leading-tight">Custom</div>
                                    <div class="text-[10px] font-medium text-[#A1A1AA]">Let's talk</div>
                                @else
                                    <div class="text-lg font-extrabold text-white leading-tight flex items-baseline justify-end">
                                        <span x-text="billing_cycle === 'annual' ? '₦{{ number_format($plan['price_annual'] / 12, 0) }}' : '₦{{ number_format($plan['price_monthly'], 0) }}'"></span>
                                        <span class="text-[10px] font-medium text-[#A1A1AA] font-normal ml-0.5">/mo</span>
                                    </div>
                                    @if($planKey !== 'enterprise')
                                    <div class="text-[10px] font-medium text-[#A1A1AA]" x-show="billing_cycle === 'annual'">
                                        Billed ₦{{ number_format($plan['price_annual'], 0) }} yearly
                                    </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-white/5">
                            <ul class="text-[10px] text-[#D4D4D8] grid grid-cols-2 gap-y-2 gap-x-2">
                                <li class="flex items-center"><span class="text-[#10B981] mr-1.5">✓</span> {{ $plan['features']['max_agents'] === -1 ? 'Unlimited' : $plan['features']['max_agents'] }} Agent(s)</li>
                                <li class="flex items-center"><span class="text-[#10B981] mr-1.5">✓</span> {{ $plan['features']['max_listings'] === -1 ? 'Unlimited' : $plan['features']['max_listings'] }} Listings</li>
                                <li class="flex items-center"><span class="text-[#10B981] mr-1.5">✓</span> {{ $plan['ai_credits_monthly'] === -1 ? 'Custom' : number_format($plan['ai_credits_monthly']) }} AI Credits</li>
                                <li class="flex items-center"><span class="text-[#10B981] mr-1.5">✓</span> {{ $plan['features']['max_portals'] === -1 ? 'Unlimited' : $plan['features']['max_portals'] }} Portals</li>
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center space-x-3 pt-4">
                <button type="button" wire:click="previousStep" 
                    class="w-24 h-[44px] border border-white/10 hover:bg-white/5 text-[#A1A1AA] hover:text-[#FAFAFA] text-sm font-semibold rounded-md transition-all flex items-center justify-center cursor-pointer">
                    Back
                </button>
                <button type="button" wire:click="nextStep" 
                    class="cta-shimmer flex-1 h-[44px] bg-[#10B981] text-white text-sm font-semibold rounded-md shadow-[0_2px_8px_rgba(16,185,129,0.16)] hover:bg-[#10B981]/90 active:scale-[0.98] transition-all flex items-center justify-center cursor-pointer">
                    Continue to security
                </button>
            </div>
        </div>
        @endif

        <!-- STEP 4: Security & Terms -->
        <div x-show="step === 4"
             x-transition:enter="transition ease-spring duration-300 transform"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-spring duration-300 transform absolute top-0 left-0 w-full"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="-translate-x-full opacity-0"
             class="space-y-5"
             style="display: none;">
             
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Set Password</label>
                <input wire:model="password" id="password" type="password" required placeholder="Min. 8 characters"
                    class="w-full h-11 bg-[#111827] border @error('password') border-[#F43F5E] @else border-white/10 @enderror text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                @error('password') 
                    <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                @enderror
            </div>

            <!-- Agree to terms of service -->
            <div class="pt-1">
                <label class="flex items-start cursor-pointer select-none">
                    <input wire:model="agree_to_terms" id="agree_to_terms" type="checkbox"
                        class="h-4 w-4 mt-0.5 bg-[#111827] border border-white/10 rounded text-[#10B981] focus:ring-offset-0 focus:ring-[#10B981]">
                    <span class="ml-2.5 text-xs text-[#A1A1AA] leading-relaxed">
                        I agree to the <a href="#" class="text-[#F59E0B] hover:underline font-semibold">Terms of Service</a> and <a href="#" class="text-[#F59E0B] hover:underline font-semibold">Privacy Policy</a> of VillaCRM.
                    </span>
                </label>
                @error('agree_to_terms') 
                    <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                @enderror
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="button" wire:click="previousStep" 
                    class="w-24 h-[44px] border border-white/10 hover:bg-white/5 text-[#A1A1AA] hover:text-[#FAFAFA] text-sm font-semibold rounded-md transition-all flex items-center justify-center cursor-pointer">
                    Back
                </button>
                <button type="submit" wire:click.prevent="submit"
                    class="cta-shimmer flex-1 h-[44px] bg-[#10B981] text-white text-sm font-semibold rounded-md shadow-[0_2px_8px_rgba(16,185,129,0.16)] hover:bg-[#10B981]/90 active:scale-[0.98] transition-all flex items-center justify-center cursor-pointer">
                    @if($invitationToken) Complete Registration @else Complete Agency Setup @endif
                </button>
            </div>
        </div>

    </div>

    <!-- Redirect footer link -->
    <div class="mt-8 pt-6 border-t border-white/5 text-center">
        <p class="text-xs text-[#A1A1AA]">
            Already have an agency?
            <a href="{{ route('login') }}" class="font-semibold text-[#F59E0B] hover:underline transition-colors ml-1">Sign in</a>
        </p>
    </div>
</div>
