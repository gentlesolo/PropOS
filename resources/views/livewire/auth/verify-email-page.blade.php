<div class="relative">

    @if (session('payment_success'))
        <div class="mb-6 p-4 rounded-md bg-[#10B981]/10 border border-[#10B981]/25 flex items-start gap-3">
            <svg class="h-5 w-5 text-[#10B981] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
            </svg>
            <p class="text-sm text-[#10B981]">{{ session('payment_success') }}</p>
        </div>
    @endif

    <!-- Header -->
    <div class="mb-8 text-center">
        <div class="h-16 w-16 bg-[#10B981]/10 rounded-full flex items-center justify-center mx-auto border border-[#10B981]/25 mb-4">
            <svg class="h-8 w-8 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
            </svg>
        </div>
        <h2 class="text-2xl font-semibold tracking-tight text-[#FAFAFA] font-sans">Check your inbox</h2>
        <p class="mt-3 text-sm text-[#A1A1AA] leading-relaxed">
            We sent a verification link to
            <span class="text-[#FAFAFA] font-medium">{{ auth()->user()->email }}</span>.
            Click the link in that email to activate your account.
        </p>
    </div>

    @if ($verificationLinkSent)
        <div class="mb-6 p-3.5 rounded-md bg-[#10B981]/10 border border-[#10B981]/20 text-[#10B981] text-xs text-center">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="mt-6 flex flex-col space-y-4">
        <button wire:click="resend" type="button" class="cta-shimmer w-full h-[44px] bg-[#10B981] text-white text-sm font-semibold rounded-md shadow-[0_2px_8px_rgba(16,185,129,0.16)] hover:bg-[#10B981]/90 active:scale-[0.98] transition-all duration-150 flex items-center justify-center cursor-pointer">
            Resend Verification Email
        </button>

        <button wire:click="logout" type="button" class="w-full h-[44px] bg-transparent border border-white/10 text-[#FAFAFA] text-sm font-semibold rounded-md hover:bg-white/5 transition-all duration-150">
            Log Out
        </button>
    </div>
</div>
