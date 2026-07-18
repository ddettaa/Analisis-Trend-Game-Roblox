@props(['compact' => false])

<a href="/" aria-label="Analisis Trend Roblox — beranda" {{ $attributes->twMerge('inline-flex items-center gap-2 font-bold tracking-tight text-foreground') }}>
    @if ($compact)
        <span aria-hidden="true" class="text-sm">R/T</span>
        <span class="sr-only">ROBLOX.TRENDS</span>
    @else
        <span>ROBLOX.TRENDS</span>
    @endif
</a>
