<?php

namespace App\Http\Livewire\Shared;

use App\Infrastructure\Tenancy\TenantResolver;
use Livewire\Component;

class Sidebar extends Component
{
    // ── SVG paths (Heroicons 2, outline, 24px) ────────────────────────────────
    private const ICONS = [
        'home'             => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'sparkles'         => 'M9.813 15.904 9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.188.904ZM19.006 8.246 18 12l-1.006-3.754L13.25 7.25l3.744-1.006L18 2.5l1.006 3.744 3.744 1.006-3.744 1.006Z',
        'users'            => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A11.386 11.386 0 0 1 10.089 20.5c-2.113 0-4.108-.577-5.837-1.587v-.109c0-1.113.285-2.16.786-3.07M14.25 11.75a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0ZM3.75 18.25v-.003c0-1.113.285-2.16.786-3.07M3.75 18.25A9.38 9.38 0 0 1 1 18a9.337 9.337 0 0 1 4.121-5.493M10.5 4.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'chart-bar'        => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
        'home-modern'      => 'M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1M15.75 3v18m-3-18v18M12 7.5h.008v.008H12V7.5Zm0 3h.008v.008H12v-.008Zm0 3h.008v.008H12v-.008Zm-3-6h.008v.008H9V7.5Zm0 3h.008v.008H9v-.008Zm0 3h.008v.008H9v-.008Z',
        'megaphone'        => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46',
        'photo'            => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
        'map'              => 'M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z',
        'building-office'  => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        'chart-pie'        => 'M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6ZM13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z',
        'heart'            => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z',
        'arrow-trending-up'=> 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941',
        'globe-alt'        => 'M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418',
        'shield-check'     => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
        'banknotes'        => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V5.998c0-.754-.726-1.294-1.453-1.096A60.064 60.064 0 0 1 2.25 5.25m0 13.5L2.25 5.25m0 13.5L15 18.75m-12.75-13.5L15 5.25m-12.75 13.5L15 18.75',
        'wallet'           => 'M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3',
        'receipt-refund'   => 'M8.25 9.75h4.875a2.625 2.625 0 0 1 0 5.25H12M8.25 9.75 10.5 7.5M8.25 9.75 10.5 12m9-7.243V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185Z',
        'calculator'       => 'M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18Zm2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.684 4.5 4.819V21a.75.75 0 0 0 1.198.601l2.302-1.725 2.302 1.725a.75.75 0 0 0 .9 0l2.302-1.725 2.302 1.725a.75.75 0 0 0 1.198-.601V4.82c0-1.135-.807-2.12-1.907-2.247A48.751 48.751 0 0 0 12 2.25Z',
        'adjustments'      => 'M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75',
        'academic-cap'     => 'M4.26 10.147a60.436 60.436 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
        'cog'              => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.43l1.004-.827c.292-.24.437-.613.43-.991a6.936 6.936 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'user-group'           => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z',
        'currency-dollar'      => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'clipboard-list'       => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'document-text'        => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        'folder-open'          => 'M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776',
        'bell-alert'           => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5',
        'squares-2x2'          => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
        'receipt-percent'      => 'M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185ZM9.75 9h.008v.008H9.75V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 4.5h.008v.008h-.008V13.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
        'magnifying-glass'     => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
        'key'                  => 'M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z',
        'inbox'                => 'M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z',
        'envelope'             => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
        'arrows-right-left'    => 'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
        'presentation-chart'   => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605',
        'tag'                  => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z',
        'arrow-up-tray'        => 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5',
    ];

    // ── Grouped navigation definition ────────────────────────────────────────
    private function groups(): array
    {
        return [
            [
                'label' => null,
                'items' => [
                    ['title' => 'Dashboard',    'route' => 'dashboard',    'icon' => 'home',     'permission' => 'dashboard.view'],
                    ['title' => 'AI Planner',   'route' => 'ai.planner',   'icon' => 'sparkles', 'permission' => 'dashboard.view'],
                ],
            ],
            [
                'label' => 'CRM',
                'items' => [
                    ['title' => 'Contacts',    'route' => 'crm.contacts', 'icon' => 'users',          'permission' => 'contacts.view_own'],
                    ['title' => 'Pipeline',    'route' => 'crm.pipeline', 'icon' => 'chart-bar',      'permission' => 'contacts.view_own'],
                    ['title' => 'Offers',      'route' => 'offers.index', 'icon' => 'tag',            'permission' => 'contacts.view_own'],
                    ['title' => 'Tasks',       'route' => 'tasks.board',  'icon' => 'clipboard-list', 'permission' => 'dashboard.view'],
                    ['title' => 'Import/Export','route' => 'crm.import',  'icon' => 'arrow-up-tray',  'permission' => 'contacts.manage'],
                ],
            ],
            [
                'label' => 'Listings',
                'items' => [
                    ['title' => 'Properties & Listings', 'route' => 'listing.index', 'icon' => 'home-modern', 'permission' => 'listings.view_own', 'active_pattern' => 'listing.*'],
                    ['title' => 'CMA Reports', 'route' => 'analytics.cma', 'icon' => 'presentation-chart', 'permission' => 'listings.view_own'],
                ],
            ],
            [
                'label' => 'Property Management',
                'items' => [
                    ['title' => 'Tenants', 'route' => 'pm.tenants', 'icon' => 'users',    'permission' => 'transactions.view_own'],
                    ['title' => 'Leases',  'route' => 'pm.leases',  'icon' => 'key',       'permission' => 'transactions.view_own'],
                ],
            ],
            [
                'label' => 'Marketing',
                'items' => [
                    ['title' => 'Campaigns',        'route' => 'marketing.campaigns',       'icon' => 'megaphone', 'permission' => 'campaigns.view_own'],
                    ['title' => 'Social Studio',    'route' => 'marketing.social',          'icon' => 'photo',     'permission' => 'campaigns.view_own'],
                    ['title' => 'Messaging Inbox',  'route' => 'marketing.inbox',           'icon' => 'inbox',     'permission' => 'campaigns.view_own'],
                    ['title' => 'Email Templates',  'route' => 'marketing.email-templates', 'icon' => 'envelope',  'permission' => 'campaigns.view_own'],
                    ['title' => 'Nurture Sequences','route' => 'marketing.sequences',       'icon' => 'clipboard-list','permission' => 'campaigns.view_own'],
                ],
            ],
            [
                'label' => 'Viewings',
                'items' => [
                    ['title' => 'Day View',     'route' => 'viewing.day',         'icon' => 'map',             'permission' => 'dashboard.view'],
                    ['title' => 'Open Houses',  'route' => 'viewing.open-houses', 'icon' => 'building-office', 'permission' => 'dashboard.view'],
                ],
            ],
            [
                'label' => 'Intelligence',
                'items' => [
                    ['title' => 'Scorecard',           'route' => 'analytics.scorecard',           'icon' => 'chart-pie',         'permission' => 'dashboard.view'],
                    ['title' => 'Listing Health',      'route' => 'analytics.listing-health',       'icon' => 'heart',             'permission' => 'dashboard.view'],
                    ['title' => 'Forecast',            'route' => 'analytics.forecast',             'icon' => 'arrow-trending-up', 'permission' => 'pipeline.view_team'],
                    ['title' => 'Portfolio Dashboard', 'route' => 'analytics.portfolio',            'icon' => 'squares-2x2',       'permission' => 'pipeline.view_team'],
                    ['title' => 'Market Intel',        'route' => 'analytics.market-intelligence',  'icon' => 'globe-alt',         'permission' => 'dashboard.view'],
                ],
            ],
            [
                'label' => 'Governance',
                'items' => [
                    ['title' => 'Document Repository', 'route' => 'governance.documents', 'icon' => 'folder-open', 'permission' => 'transactions.view_own'],
                    ['title' => 'Compliance Calendar', 'route' => 'compliance.calendar',  'icon' => 'bell-alert',  'permission' => 'transactions.view_own'],
                ],
            ],
            [
                'label' => 'Compliance & Finance',
                'items' => [
                    ['title' => 'Transactions',       'route' => 'compliance.transactions', 'icon' => 'shield-check',     'permission' => 'transactions.view_own'],
                    ['title' => 'Contracts',          'route' => 'contracts.index',         'icon' => 'document-text',    'permission' => 'transactions.view_own'],
                    ['title' => 'Inspections',        'route' => 'compliance.inspections',  'icon' => 'magnifying-glass', 'permission' => 'transactions.view_own'],
                    ['title' => 'Commissions',        'route' => 'finance.commissions',     'icon' => 'banknotes',        'permission' => 'commission.view_own'],
                    ['title' => 'Invoices',           'route' => 'finance.invoices',        'icon' => 'wallet',           'permission' => 'transactions.view_own'],
                    ['title' => 'Expenses',           'route' => 'finance.expenses',        'icon' => 'receipt-refund',   'permission' => 'transactions.view_own'],
                    ['title' => 'Budgeting',          'route' => 'finance.budgeting',       'icon' => 'calculator',       'permission' => 'transactions.view_own'],
                    ['title' => 'Financial Reports',  'route' => 'finance.reports',         'icon' => 'chart-bar',        'permission' => 'transactions.view_own'],
                ],
            ],
            [
                'label' => 'Training',
                'items' => [
                    ['title' => 'Training Hub', 'route' => 'training.dashboard', 'icon' => 'academic-cap', 'permission' => 'training.view'],
                ],
            ],
            [
                'label' => 'Settings',
                'items' => [
                    ['title' => 'Settings',           'route' => 'settings.profile',          'icon' => 'cog',             'permission' => 'agency.view'],
                    ['title' => 'Team',               'route' => 'settings.team',             'icon' => 'user-group',      'permission' => 'agency.manage'],
                    ['title' => 'Commission Splits',  'route' => 'settings.commission-splits','icon' => 'receipt-percent',  'permission' => 'agency.manage'],
                    ['title' => 'Lead Routing',       'route' => 'settings.lead-routing',     'icon' => 'arrows-right-left','permission' => 'agency.manage'],
                    ['title' => 'Pipeline Stages',    'route' => 'settings.pipeline-stages',  'icon' => 'clipboard-list',   'permission' => 'agency.manage'],
                    ['title' => 'Tax Configuration',  'route' => 'settings.tax',              'icon' => 'adjustments',      'permission' => 'agency.manage'],
                    ['title' => 'Website Integration', 'route' => 'settings.website-integration','icon' => 'globe-alt',        'permission' => 'agency.manage'],
                    ['title' => 'API Keys',           'route' => 'settings.api-keys',         'icon' => 'key',              'permission' => 'agency.manage'],
                    ['title' => 'Webhooks',           'route' => 'settings.webhooks',         'icon' => 'arrows-right-left','permission' => 'agency.manage'],
                ],
            ],
        ];
    }

    public function render()
    {
        $agency = app(TenantResolver::class)->getCurrentAgency();
        $user   = auth()->user();
        $icons  = self::ICONS;

        $groups = collect($this->groups())
            ->map(function (array $group) use ($user, $icons) {
                $items = collect($group['items'])
                    ->filter(fn($item) => ! $user || $user->hasPermissionTo($item['permission']))
                    ->map(fn($item) => array_merge($item, [
                        'svg'    => $icons[$item['icon']] ?? '',
                        'active' => request()->routeIs($item['active_pattern'] ?? $item['route']),
                    ]))
                    ->values()
                    ->all();

                return ['label' => $group['label'], 'items' => $items];
            })
            ->filter(fn($group) => ! empty($group['items']))
            ->values()
            ->all();

        return view('livewire.shared.sidebar', compact('agency', 'groups'));
    }
}
