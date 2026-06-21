<div>
@if($open)
<div class="fixed bottom-0 right-6 z-[100] flex flex-col"
     style="width: 560px; max-width: calc(100vw - 2rem);">

    {{-- Title bar --}}
    <div class="bg-text-primary dark:bg-surface-elevated rounded-t-xl flex items-center justify-between px-4 py-2.5 cursor-pointer select-none"
         wire:click="minimize">
        <span class="text-sm font-semibold text-white dark:text-text-primary">
            {{ $subject ?: 'New Message' }}
        </span>
        <div class="flex items-center gap-2" x-on:click.stop>
            <button wire:click="minimize" title="{{ $minimized ? 'Expand' : 'Minimise' }}"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative text-white/70 hover:text-white dark:text-text-tertiary dark:hover:text-text-primary transition" wire:loading.attr="disabled" wire:target="minimize">
                <span wire:loading.remove wire:target="minimize"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    @if($minimized)
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    @endif
                </svg></span>
                <span wire:loading wire:target="minimize" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            <button wire:click="close" title="Close"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative text-white/70 hover:text-white dark:text-text-tertiary dark:hover:text-text-primary transition" wire:loading.attr="disabled" wire:target="close">
                <span wire:loading.remove wire:target="close"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg></span>
                <span wire:loading wire:target="close" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        </div>
    </div>

    @unless($minimized)
    {{-- Composer body --}}
    <div class="bg-surface-card border border-border-default border-t-0 shadow-2xl rounded-b-xl flex flex-col"
         style="max-height: 520px;">

        {{-- To + Subject fields --}}
        <div class="border-b border-border-default">
            <div class="flex items-center px-4 py-2 gap-2 border-b border-border-default/50">
                <span class="text-xs font-medium text-text-tertiary w-10 shrink-0">To</span>
                <input type="email" wire:model.lazy="to_email" placeholder="recipient@example.com"
                       class="flex-1 text-sm bg-transparent outline-none text-text-primary placeholder-text-tertiary">
                @error('to_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="flex items-center px-4 py-2 gap-2">
                <span class="text-xs font-medium text-text-tertiary w-10 shrink-0">Subject</span>
                <input type="text" wire:model.lazy="subject" placeholder="Subject"
                       class="flex-1 text-sm bg-transparent outline-none text-text-primary placeholder-text-tertiary">
                @error('subject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Toolbar: From + Template --}}
        <div class="flex items-center gap-3 px-4 py-2 border-b border-border-default/50 bg-surface-elevated/40">
            @if($accounts->count() > 1)
            <div class="flex items-center gap-1.5">
                <span class="text-xs text-text-tertiary">From</span>
                <select wire:model="email_account_id"
                        class="text-xs bg-transparent border-0 outline-none text-text-secondary cursor-pointer">
                    <option value="">Default</option>
                    @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->email_address }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-px h-4 bg-border-default"></div>
            @endif

            @if($templates->isNotEmpty())
            <div class="flex items-center gap-1.5">
                <span class="text-xs text-text-tertiary">Template</span>
                <select wire:model="template_id"
                        class="text-xs bg-transparent border-0 outline-none text-text-secondary cursor-pointer max-w-[160px] truncate">
                    <option value="">— Select —</option>
                    @foreach($templates as $tpl)
                    <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto">
            <textarea wire:model.lazy="body_html"
                      placeholder="Write your message here…"
                      class="w-full h-52 resize-none p-4 text-sm text-text-primary bg-transparent outline-none placeholder-text-tertiary leading-relaxed font-sans"
            ></textarea>
            @error('body_html') <p class="text-red-500 text-xs px-4 pb-2">{{ $message }}</p> @enderror
        </div>

        {{-- Footer actions --}}
        <div class="flex items-center justify-between px-4 py-3 border-t border-border-default">
            <div class="flex items-center gap-2 text-text-tertiary">
                <button title="Attach file (coming soon)" class="hover:text-text-secondary transition" disabled>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="close"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-3 py-1.5 text-xs font-medium text-text-secondary hover:text-text-primary transition" wire:loading.attr="disabled" wire:target="close">
                <span wire:loading.remove wire:target="close">Discard</span>
                <span wire:loading wire:target="close" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="send"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60 cursor-not-allowed"
                        class="px-4 py-1.5 bg-brand-primary text-white text-xs font-semibold rounded-lg hover:opacity-90 transition flex items-center gap-1.5">
                    <span wire:loading.remove wire:target="send">Send</span>
                    <span wire:loading wire:target="send">Sending…</span>
                    <svg wire:loading.remove wire:target="send" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endunless
</div>
@endif
</div>
