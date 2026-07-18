@php
    $items = [
        ['page' => 'ringkasan', 'href' => '/dashboard', 'icon' => 'bar-chart-3', 'label' => 'Ringkasan'],
        ['page' => 'saturasi', 'href' => '/dashboard/saturasi', 'icon' => 'gauge', 'label' => 'Saturasi'],
        ['page' => 'viral', 'href' => '/dashboard/viral', 'icon' => 'flame', 'label' => 'Viral Muda'],
    ];
    $activePage = request()->is('dashboard/saturasi') ? 'saturasi' : (request()->is('dashboard/viral') ? 'viral' : 'ringkasan');
@endphp

<nav data-ui="dashboard-nav" aria-label="Navigasi dashboard">
    <div class="fixed inset-y-14 left-0 z-30 hidden w-24 border-r border-border bg-card/95 px-2 py-4 lg:flex lg:flex-col">
        <x-analytics.brand-mark compact class="justify-center py-2" />
        <div class="mt-6 space-y-2">
            @foreach ($items as $item)
                @php($isActive = $item['page'] === $activePage)
                <a href="{{ $item['href'] }}" data-page="{{ $item['page'] }}" @if ($isActive) aria-current="page" @endif aria-label="{{ $item['label'] }}" class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-lg px-2 text-xs font-medium {{ $isActive ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground' }}">
                    <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-4" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div data-ui="mobile-dashboard-nav" class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-card/95 lg:hidden" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="grid h-16 grid-cols-3 px-1">
            @foreach ($items as $item)
                @php($isActive = $item['page'] === $activePage)
                <a href="{{ $item['href'] }}" data-page="{{ $item['page'] }}" @if ($isActive) aria-current="page" @endif aria-label="{{ $item['label'] }}" class="flex min-h-16 flex-col items-center justify-center gap-1 rounded-md px-2 text-xs font-medium {{ $isActive ? 'text-foreground' : 'text-muted-foreground' }}">
                    <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-4" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</nav>
