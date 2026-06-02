@props([
    'rows' => 5,
    'cols' => 5
])

<div {{ $attributes->merge(['class' => 'w-full animate-pulse']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-border-default/60">
            <thead class="bg-surface-sunken/20">
                <tr>
                    @for($i = 0; $i < $cols; $i++)
                    <th class="px-6 py-4 text-left">
                        <div class="h-3 w-20 bg-surface-raised rounded-full"></div>
                    </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default/60 bg-white/5">
                @for($i = 0; $i < $rows; $i++)
                <tr>
                    @for($j = 0; $j < $cols; $j++)
                    <td class="px-6 py-5 whitespace-nowrap">
                        @if($j == 0)
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-surface-raised shrink-0"></div>
                            <div class="space-y-2">
                                <div class="h-3.5 w-32 bg-surface-raised rounded-full"></div>
                                <div class="h-2.5 w-24 bg-surface-sunken rounded-full"></div>
                            </div>
                        </div>
                        @elseif($j == $cols - 1)
                        <div class="flex justify-end gap-2">
                            <div class="h-8 w-16 bg-surface-raised rounded-lg"></div>
                        </div>
                        @else
                        <div class="h-3 w-{{ rand(16, 24) }} bg-surface-raised rounded-full"></div>
                        @endif
                    </td>
                    @endfor
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
