<div class="max-w-lg mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('settings.profile') }}" class="text-text-tertiary hover:text-brand-primary text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Settings
        </a>
        <span class="text-text-tertiary">/</span>
        <span class="text-sm font-medium text-text-secondary">Two-Factor Authentication</span>
    </div>

    <div class="bg-surface-card rounded-2xl border border-border-default p-6">
        @if($enabled)
        <!-- 2FA is ON -->
        <div class="text-center mb-6">
            <div class="h-12 w-12 bg-success-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                <svg class="h-6 w-6 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-text-primary">2FA is Enabled</h2>
            <p class="text-sm text-text-secondary mt-1">Your account is protected with two-factor authentication.</p>
        </div>

        @if(!$showDisableConfirm)
        <button wire:click="$set('showDisableConfirm', true)" class="w-full py-2 border border-danger-300 text-danger-600 rounded-xl text-sm font-medium hover:bg-danger-50 transition-colors">
            Disable Two-Factor Authentication
        </button>
        @else
        <div class="space-y-4">
            <p class="text-sm text-text-secondary">Enter your password to confirm disabling 2FA:</p>
            <div>
                <input wire:model.defer="disable_password" type="password" placeholder="Your password"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                @error('disable_password') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div class="flex gap-3">
                <button wire:click="disable" class="flex-1 py-2 bg-danger-600 text-white rounded-xl text-sm font-medium hover:bg-danger-700 transition-colors">
                    <span wire:loading.remove wire:target="disable">Disable 2FA</span>
                    <span wire:loading wire:target="disable">Disabling...</span>
                </button>
                <button wire:click="$set('showDisableConfirm', false)" class="flex-1 py-2 border border-border-default text-text-secondary rounded-xl text-sm font-medium hover:bg-surface-sunken transition-colors">Cancel</button>
            </div>
        </div>
        @endif

        @else
        <!-- 2FA Setup Flow -->
        <div class="mb-5">
            <h2 class="text-lg font-bold text-text-primary">Enable Two-Factor Authentication</h2>
            <p class="text-sm text-text-secondary mt-1">Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code to confirm.</p>
        </div>

        @if($qrCodeUrl)
        <div class="flex flex-col items-center mb-5">
            <div class="bg-white p-4 rounded-xl border border-border-default mb-3">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)->generate($qrCodeUrl) !!}
            </div>
            <p class="text-xs text-text-secondary text-center">Can't scan? Enter this key manually:</p>
            <code class="mt-1 px-3 py-1 bg-surface-sunken rounded-lg text-xs font-mono tracking-widest text-text-primary">{{ $secret }}</code>
        </div>
        @endif

        <form wire:submit.prevent="enable" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-primary mb-1">Verification Code</label>
                <input wire:model.defer="code" type="text" inputmode="numeric" maxlength="6" placeholder="000000"
                    class="w-full rounded-xl border border-border-default bg-surface-input px-4 py-3 text-center text-xl font-mono tracking-widest text-text-primary focus:border-brand-primary focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page/20">
                @error('code') <span class="text-xs text-danger-600 mt-1 block text-center">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="w-full py-3 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl font-semibold hover:bg-brand-secondary transition-colors">
                <span wire:loading.remove wire:target="enable">Enable 2FA</span>
                <span wire:loading wire:target="enable">Verifying...</span>
            </button>
        </form>
        @endif
    </div>
</div>



