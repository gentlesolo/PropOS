<div>
    <!-- Logo & Header -->
    <div class="mb-8">
        <div class="text-3xl font-extrabold tracking-tight bg-gradient-brand bg-clip-text text-transparent">
            PropOS
        </div>
        <h2 class="mt-6 text-2xl font-bold tracking-tight text-text-primary">Create your agency</h2>
        <p class="mt-2 text-sm text-text-secondary">
            Or
            <a href="{{ route('login') }}" class="font-semibold text-brand-primary hover:text-brand-secondary transition-colors">sign in to your account</a>
        </p>
    </div>

    <!-- Form -->
    <form wire:submit.prevent="submit" class="space-y-5">
        <!-- Agency Details -->
        <div class="border-b border-border-subtle pb-5 mb-5">
            <h3 class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-4">Agency Details</h3>
            
            <div class="space-y-4">
                <div>
                    <label for="agency_name" class="block text-sm font-semibold text-text-primary">Agency Name</label>
                    <div class="mt-1.5">
                        <input wire:model.blur="agency_name" id="agency_name" type="text" required class="block w-full rounded-xl border border-border-default py-2.5 px-3.5 text-text-primary bg-surface-input shadow-sm placeholder:text-text-tertiary focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary sm:text-sm">
                        @error('agency_name') <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label for="slug" class="block text-sm font-semibold text-text-primary">Agency Slug / ID</label>
                    <div class="mt-1.5">
                        <input wire:model="slug" id="slug" type="text" required class="block w-full rounded-xl border border-border-default py-2.5 px-3.5 text-text-primary bg-surface-input shadow-sm placeholder:text-text-tertiary focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary sm:text-sm">
                        @error('slug') <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Principal Details -->
        <div>
            <h3 class="text-xs font-bold text-text-tertiary uppercase tracking-wider mb-4">Owner Profile</h3>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-semibold text-text-primary">First Name</label>
                        <div class="mt-1.5">
                            <input wire:model="first_name" id="first_name" type="text" required class="block w-full rounded-xl border border-border-default py-2.5 px-3.5 text-text-primary bg-surface-input shadow-sm placeholder:text-text-tertiary focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary sm:text-sm">
                            @error('first_name') <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-semibold text-text-primary">Last Name</label>
                        <div class="mt-1.5">
                            <input wire:model="last_name" id="last_name" type="text" required class="block w-full rounded-xl border border-border-default py-2.5 px-3.5 text-text-primary bg-surface-input shadow-sm placeholder:text-text-tertiary focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary sm:text-sm">
                            @error('last_name') <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-text-primary">Email Address</label>
                    <div class="mt-1.5">
                        <input wire:model="email" id="email" type="email" required class="block w-full rounded-xl border border-border-default py-2.5 px-3.5 text-text-primary bg-surface-input shadow-sm placeholder:text-text-tertiary focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary sm:text-sm">
                        @error('email') <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold text-text-primary">Phone Number (Optional)</label>
                    <div class="mt-1.5">
                        <input wire:model="phone" id="phone" type="text" class="block w-full rounded-xl border border-border-default py-2.5 px-3.5 text-text-primary bg-surface-input shadow-sm placeholder:text-text-tertiary focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary sm:text-sm">
                        @error('phone') <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-text-primary">Password</label>
                    <div class="mt-1.5">
                        <input wire:model="password" id="password" type="password" required class="block w-full rounded-xl border border-border-default py-2.5 px-3.5 text-text-primary bg-surface-input shadow-sm placeholder:text-text-tertiary focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary sm:text-sm">
                        @error('password') <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="flex w-full justify-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold leading-6 text-white shadow-sm hover:opacity-90 hover:shadow-brand-md hover-spring focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-primary cursor-pointer">
                Get Started
            </button>
        </div>
    </form>
</div>
