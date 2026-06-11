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
         x-init="$watch('success', value => { if (value) { setTimeout(() => { window.location.href = '{{ route('login') }}'; }, 2000); } })" 
         style="display: none;" 
         class="absolute -inset-6 bg-[#090d16]/95 backdrop-blur-md flex flex-col items-center justify-center z-50 rounded-xl transition-all duration-300">
        <div class="flex flex-col items-center space-y-4">
            <div class="h-16 w-16 bg-[#10B981]/10 rounded-full flex items-center justify-center border border-[#10B981]/30 animate-pulse">
                <svg class="h-8 w-8 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <div class="text-center">
                <h3 class="text-lg font-semibold text-[#FAFAFA]">Password Reset Successful</h3>
                <p class="text-xs text-[#A1A1AA] mt-1">Redirecting to login...</p>
            </div>
        </div>
    </div>

    <div>
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-semibold tracking-tight text-[#FAFAFA] font-sans">Set new password</h2>
            <p class="mt-2 text-sm text-[#A1A1AA]">
                Enter your new password below to regain access to your account.
            </p>
        </div>

        <!-- Form -->
        <form wire:submit.prevent="resetPassword" class="space-y-5">
            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Email Address</label>
                <input wire:model="email" id="email" type="email" required autocomplete="email" placeholder="agent@agency.com"
                    class="w-full h-11 bg-[#111827] border @error('email') border-[#F43F5E] @else border-white/10 @enderror text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                @error('email') 
                    <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">New Password</label>
                <input wire:model="password" id="password" type="password" required placeholder="••••••••"
                    class="w-full h-11 bg-[#111827] border @error('password') border-[#F43F5E] @else border-white/10 @enderror text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
                @error('password') 
                    <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> 
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Confirm Password</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" required placeholder="••••••••"
                    class="w-full h-11 bg-[#111827] border border-white/10 text-sm text-[#FAFAFA] placeholder-[#52525B] px-3.5 rounded-md focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
            </div>

            <div class="pt-2">
                <button type="submit" class="cta-shimmer w-full h-[44px] bg-[#10B981] text-white text-sm font-semibold rounded-md shadow-[0_2px_8px_rgba(16,185,129,0.16)] hover:bg-[#10B981]/90 active:scale-[0.98] transition-all duration-150 flex items-center justify-center cursor-pointer">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>
