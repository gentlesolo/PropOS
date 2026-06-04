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
    'border-zinc-800': 'border-border-strong',
    'border-zinc-850': 'border-border-default',
    'border-zinc-900': 'border-border-default',
    'border-slate-800': 'border-border-strong',
    'divide-zinc-800': 'divide-border-strong',
    'divide-zinc-900': 'divide-border-default',
    'bg-zinc-900': 'bg-surface-raised',
    'bg-zinc-950': 'bg-surface-sunken',
    'bg-zinc-800': 'bg-surface-card',
    'text-zinc-400': 'text-text-secondary',
    'text-zinc-500': 'text-text-tertiary',
    'text-zinc-600': 'text-text-tertiary',
    'text-zinc-550': 'text-text-tertiary',
    'text-slate-400': 'text-text-secondary',
    'text-slate-500': 'text-text-tertiary'
};

for (const relPath of files) {
    const fullPath = path.join(basePath, relPath);
    if (!fs.existsSync(fullPath)) {
        continue;
    }
    
    let content = fs.readFileSync(fullPath, 'utf8');
    
    // Replace zinc/slate classes
    for (const [pattern, replacement] of Object.entries(replacements)) {
        const regex = new RegExp(pattern, 'g');
        content = content.replace(regex, replacement);
    }
    
    // Carefully replace text-white
    // We want to replace text-white with text-text-primary ONLY if it is not on a button/badge that is brightly colored.
    // A simple heuristic: if the line contains 'bg-brand-' or 'bg-success' or 'bg-danger' or 'bg-warning', leave it.
    // Wait, regex might be hard. Let's process line by line or tag by tag.
    const lines = content.split('\n');
    const newLines = lines.map(line => {
        if (line.includes('text-white')) {
            // Check for brand/colored backgrounds in the same line
            if (line.includes('bg-brand') || line.includes('bg-success') || line.includes('bg-danger') || line.includes('bg-warning') || line.includes('bg-[#') || line.includes('text-white/')) {
                // keep it
                return line;
            }
            return line.replace(/text-white/g, 'text-text-primary');
        }
        return line;
    });
    
    fs.writeFileSync(fullPath, newLines.join('\n'), 'utf8');
    console.log(`Updated ${relPath}`);
}
