<div class="relative">
    <style>
        @keyframes slide-down {
            0% { opacity: 0; transform: translateY(-4px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-down {
            animation: slide-down 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>

    <!-- Success checkmark overlay before redirect -->
    <div x-data="{ success: @entangle('isSuccessful') }" 
         x-show="success" 
         x-init="$watch('success', value => { if (value) { setTimeout(() => { window.location.href = '{{ route('dashboard') }}'; }, 1000); } })" 
         style="display: none;" 
         class="absolute -inset-6 bg-[#090d16]/95 backdrop-blur-md flex flex-col items-center justify-center z-50 rounded-xl transition-all duration-300">
        <div class="flex flex-col items-center space-y-4">
            <div class="h-16 w-16 bg-[#10B981]/10 rounded-full flex items-center justify-center border border-[#10B981]/30 animate-pulse">
                <svg class="h-8 w-8 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <div class="text-center">
                <h3 class="text-lg font-semibold text-[#FAFAFA]">Sign-in Successful</h3>
                <p class="text-xs text-[#A1A1AA] mt-1">Connecting to control room...</p>
            </div>
        </div>
    </div>

    <!-- Mode: LOGIN -->
    @if($mode === 'login')
        <div>
            <!-- Header -->
            <div class="mb-8">
                <!-- Mini mobile logo mark (visible on mobile only) -->
                <div class="lg:hidden flex items-center space-x-2.5 mb-6">
                    <div class="h-8 w-8 rounded-md bg-[#10B981]/10 flex items-center justify-center border border-[#10B981]/25">
                        <span class="font-bold text-base text-[#10B981]">P</span>
                    </div>
                    <span class="text-lg font-bold tracking-tight text-[#FAFAFA]">VillaCRM</span>
                </div>

                <h2 class="text-2xl font-semibold tracking-tight text-[#FAFAFA] font-sans">Welcome back</h2>
                <p class="mt-2 text-sm text-[#A1A1AA]">
                    Enter your credentials to access the property operating system.
                </p>
            </div>

            <!-- Error Alerts -->
            @if(session()->has('error'))
                <div class="mb-5 p-3.5 rounded-md bg-[#F43F5E]/10 border border-[#F43F5E]/20 text-[#F43F5E] text-xs animate-slide-down">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Form -->
            <form wire:submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Email Address</label>
                    <div class="relative">
                        <input wire:model="email" id="email" type="email" required autocomplete="email" placeholder="agent@agency.com"
                            class="w-full h-11 bg-[#111827] border @error('email') border-[#F43F5E] @else border-white/10 @enderror text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                    </div>
                    @error('email') 
                        <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                    @enderror
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA]">Password</label>
                        <button type="button" wire:click="showForgotPassword" class="text-xs font-semibold text-[#F59E0B] hover:underline focus:outline-none">
                            Forgot password?
                        </button>
                    </div>
                    <div class="relative">
                        <input wire:model="password" id="password" type="password" required placeholder="••••••••"
                            class="w-full h-11 bg-[#111827] border @error('password') border-[#F43F5E] @else border-white/10 @enderror text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                    </div>
                    @error('password') 
                        <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- Remember Me + Biometric Button -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center cursor-pointer select-none">
                        <input wire:model="remember" id="remember" type="checkbox" 
                            class="h-4 w-4 bg-[#111827] border border-white/10 rounded text-[#10B981] focus:ring-offset-0 focus:ring-[#10B981]">
                        <span class="ml-2.5 text-xs text-[#A1A1AA]">Remember this device</span>
                    </label>

                    <!-- Biometric Fingerprint Trigger (Visual Hint) -->
                    <button type="button" title="Sign in with Passkey / Biometrics"
                        class="p-2 rounded bg-[#111827] border border-white/10 text-[#A1A1AA] hover:text-[#10B981] hover:border-[#10B981]/30 transition-colors focus:outline-none focus:ring-1 focus:ring-[#10B981]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A9.001 9.001 0 0120.828 10.95M19.117 14.99a7.5 7.5 0 01-12.012 0M10.27 18.062a4.999 4.999 0 016.96 0m-4.877-9.062a2.5 2.5 0 114.878 0v2.25H12.35V9Z"/>
                        </svg>
                    </button>
                </div>

                <div>
                    <button type="submit" class="cta-shimmer w-full h-[44px] bg-[#10B981] text-white text-sm font-semibold rounded-md shadow-[0_2px_8px_rgba(16,185,129,0.16)] hover:bg-[#10B981]/90 active:scale-[0.98] transition-all duration-150 flex items-center justify-center cursor-pointer">
                        Sign in to VillaCRM
                    </button>
                </div>
            </form>

            <!-- Bottom Redirect -->
            <div class="mt-8 pt-6 border-t border-white/5 text-center">
                <a href="{{ route('register') }}" class="text-xs text-[#A1A1AA] hover:text-[#FAFAFA] transition-colors inline-flex items-center space-x-1">
                    <span>New agency? Request access</span>
                    <span class="text-[#F59E0B] font-semibold">→</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Mode: FORGOT PASSWORD -->
    @if($mode === 'forgot_password')
        <div>
            <!-- Header -->
            <div class="mb-8">
                <h2 class="text-2xl font-semibold tracking-tight text-[#FAFAFA] font-sans">Reset your access</h2>
                <p class="mt-2 text-sm text-[#A1A1AA]">
                    Enter your email address and we'll dispatch a secure recovery link.
                </p>
            </div>

            <!-- Form -->
            <form wire:submit.prevent="sendResetLink" class="space-y-5">
                <div>
                    <label for="reset_email" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Email Address</label>
                    <input wire:model="email" id="reset_email" type="email" required placeholder="agent@agency.com"
                        class="w-full h-11 bg-[#111827] border @error('email') border-[#F43F5E] @else border-white/10 @enderror text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                    @error('email') 
                        <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                    @enderror
                </div>

                <div>
                    <button type="submit" class="cta-shimmer w-full h-[44px] bg-[#10B981] text-white text-sm font-semibold rounded-md shadow-[0_2px_8px_rgba(16,185,129,0.16)] hover:bg-[#10B981]/90 active:scale-[0.98] transition-all duration-150 flex items-center justify-center cursor-pointer">
                        Send reset link
                    </button>
                </div>
            </form>

            <!-- Bottom Redirect -->
            <div class="mt-8 pt-6 border-t border-white/5 text-center">
                <button type="button" wire:click="showLogin" class="text-xs text-[#A1A1AA] hover:text-[#FAFAFA] transition-colors focus:outline-none">
                    Back to login
                </button>
            </div>
        </div>
    @endif

    <!-- Mode: FORGOT PASSWORD SUCCESS -->
    @if($mode === 'forgot_password_success')
        <div class="text-center py-4 space-y-6">
            <div class="h-16 w-16 bg-[#10B981]/10 rounded-full flex items-center justify-center mx-auto border border-[#10B981]/25 animate-pulse">
                <svg class="h-7 w-7 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
            </div>
            
            <div class="space-y-2">
                <h2 class="text-xl font-semibold text-[#FAFAFA]">Check your inbox</h2>
                <p class="text-sm text-[#A1A1AA] max-w-sm mx-auto leading-relaxed">
                    We have dispatched a secure authentication recovery link to <span class="text-[#FAFAFA] font-medium">{{ $email }}</span>.
                </p>
            </div>

            <div class="pt-4">
                <button type="button" wire:click="showLogin" class="inline-flex items-center justify-center px-6 h-10 border border-white/10 rounded-md text-xs text-[#FAFAFA] hover:bg-white/5 transition-all">
                    Return to sign in
                </button>
            </div>
        </div>
    @endif
</div>
