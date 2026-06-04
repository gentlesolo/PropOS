const fs = require('fs');
const path = require('path');

const files = [
    'resources/views/livewire/dashboard-page-v2.blade.php',
    'resources/views/livewire/dashboard-page.blade.php',
    'resources/views/livewire/crm/contacts-page.blade.php',
    'resources/views/livewire/crm/pipeline-board.blade.php',
    'resources/views/livewire/offers/offers-page.blade.php',
    'resources/views/livewire/tasks/task-board-page.blade.php',
    'resources/views/livewire/listing/index-page.blade.php'
];

const basePath = 'c:/Users/ADMIN/Herd/propos/';

const replacements = {
    'bg-\\[#030712\\]': 'bg-surface-page',
    'bg-\\[#090d16\\]': 'bg-surface-card',
    'bg-\\[#111827\\]': 'bg-surface-raised',
    'bg-\\[#050811\\]': 'bg-surface-sunken',
    'border-white/5': 'border-border-default',
    'border-white/10': 'border-border-strong',
    'border-\\[#111827\\]': 'border-border-strong',
    'divide-white/5': 'divide-border-default',
    'divide-white/10': 'divide-border-strong',
    'ring-white/5': 'ring-border-default',
    'ring-white/10': 'ring-border-strong',
    'ring-\\[#030712\\]': 'ring-surface-page',
    'ring-\\[#090d16\\]': 'ring-surface-card',
    'text-\\[#FAFAFA\\]': 'text-text-primary',
    'text-\\[#A1A1AA\\]': 'text-text-secondary',
    'text-\\[#52525B\\]': 'text-text-tertiary',
    'text-\\[#71717A\\]': 'text-text-tertiary',
    'text-\\[#71717a\\]': 'text-text-tertiary',
    'text-\\[#3F3F46\\]': 'text-text-disabled',
    'text-\\[#3f3f46\\]': 'text-text-disabled',
    'hover:bg-\\[#111827\\]': 'hover:bg-state-hover-bg',
    'hover:bg-\\[#030712\\]': 'hover:bg-state-hover-bg',
    'hover:bg-white/5': 'hover:bg-state-hover-bg',
    'hover:bg-\\[#090d16\\]': 'hover:bg-state-hover-bg',
    'text-\\[#10B981\\]': 'text-brand-primary',
    'text-\\[#34d399\\]': 'text-text-link',
    'text-\\[#34D399\\]': 'text-text-link',
    'hover:text-\\[#FAFAFA\\]': 'hover:text-text-primary',
    'hover:text-\\[#10B981\\]': 'hover:text-brand-primary',
    'hover:text-\\[#34d399\\]': 'hover:text-text-link',
    'bg-\\[#10B981\\]': 'bg-brand-primary',
    'bg-\\[#F59E0B\\]': 'bg-brand-accent',
    'bg-\\[#F43F5E\\]': 'bg-color-danger-500',
    'bg-\\[#22C55E\\]': 'bg-color-success-500',
    'text-\\[#F43F5E\\]': 'text-color-danger-500',
    'text-\\[#F59E0B\\]': 'text-color-warning-500',
    'text-\\[#22C55E\\]': 'text-color-success-500',
    'border-\\[#10B981\\]': 'border-border-focus',
    'shadow-\\[0_0_8px_#10B981\\]': 'shadow-brand-sm',
    'shadow-[#10B981]/10': 'shadow-brand-sm',
    'shadow-[#10B981]/15': 'shadow-brand-sm',
    'shadow-[#10B981]/20': 'shadow-brand-md',
    'bg-gradient-to-br from-\\[#10B981\\] to-\\[#10B981\\]/80': 'bg-gradient-brand',
    'bg-gradient-to-r from-\\[#10B981\\] to-\\[#0ea5e9\\]': 'bg-gradient-brand-vibrant',
    'bg-gradient-to-br from-\\[#10B981\\] to-\\[#0ea5e9\\]': 'bg-gradient-brand-vibrant'
};

for (const relPath of files) {
    const fullPath = path.join(basePath, relPath);
    if (!fs.existsSync(fullPath)) {
        console.log(`Skipping ${relPath} - not found`);
        continue;
    }
    
    let content = fs.readFileSync(fullPath, 'utf8');
    
    for (const [pattern, replacement] of Object.entries(replacements)) {
        const regex = new RegExp(pattern, 'g');
        content = content.replace(regex, replacement);
    }
    
    fs.writeFileSync(fullPath, content, 'utf8');
    console.log(`Updated ${relPath}`);
}
