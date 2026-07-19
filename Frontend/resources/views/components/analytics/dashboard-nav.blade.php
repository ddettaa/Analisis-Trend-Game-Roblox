@php
    $items = [
        ['page' => 'ringkasan', 'href' => '/dashboard', 'icon' => 'bar-chart-3', 'label' => 'Overview'],
        ['page' => 'saturasi', 'href' => '/dashboard/saturasi', 'icon' => 'gauge', 'label' => 'Saturation'],
        ['page' => 'viral', 'href' => '/dashboard/viral', 'icon' => 'flame', 'label' => 'Momentum'],
    ];
    $activePage = request()->is('dashboard/saturasi') ? 'saturasi' : (request()->is('dashboard/viral') ? 'viral' : 'ringkasan');
@endphp

<nav data-ui="dashboard-nav" aria-label="Navigasi dashboard" {{ $attributes->except(['data-ui', 'aria-label'])->twMerge('') }}>
    <div data-ui="icon-rail" class="fixed bottom-0 left-0 top-14 z-30 hidden w-16 flex-col items-center border-r border-border bg-background/95 py-3 backdrop-blur lg:flex">
        <div class="flex w-full flex-col items-center gap-2">
            @foreach ($items as $item)
                @php($isActive = $item['page'] === $activePage)
                <a
                    href="{{ $item['href'] }}"
                    data-page="{{ $item['page'] }}"
                    @if ($isActive) aria-current="page" @endif
                    aria-label="{{ $item['label'] }}"
                    title="{{ $item['label'] }}"
                    class="grid size-10 place-items-center rounded-lg border outline-none transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background {{ $isActive ? 'border-border bg-card text-foreground shadow-xs' : 'border-transparent text-muted-foreground hover:border-border/70 hover:bg-card/60 hover:text-foreground' }}"
                >
                    <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-[18px]" aria-hidden="true" />
                    <span class="sr-only">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div data-ui="mobile-dashboard-nav" class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-background/95 backdrop-blur lg:hidden" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="grid h-16 grid-cols-3 px-2">
            @foreach ($items as $item)
                @php($isActive = $item['page'] === $activePage)
                <a
                    href="{{ $item['href'] }}"
                    data-page="{{ $item['page'] }}"
                    @if ($isActive) aria-current="page" @endif
                    aria-label="{{ $item['label'] }}"
                    class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-md px-2 text-[11px] outline-none transition-colors focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring {{ $isActive ? 'font-semibold text-foreground' : 'font-medium text-muted-foreground hover:text-foreground' }}"
                >
                    <span class="grid size-7 place-items-center rounded-md border {{ $isActive ? 'border-border bg-card shadow-xs' : 'border-transparent' }}">
                        <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-4" aria-hidden="true" />
                    </span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</nav>
