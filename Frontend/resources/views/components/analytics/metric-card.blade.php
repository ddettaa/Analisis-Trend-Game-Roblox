@props(['label', 'value', 'annotation' => null])

<x-ui.card data-ui="metric-card" {{ $attributes->twMerge('editorial-card space-y-3') }}>
    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">{{ $label }}</p>
    <p class="text-3xl font-bold tabular-nums tracking-tight">{{ $value }}</p>
    @if ($annotation)
        <p class="text-sm text-muted-foreground">{{ $annotation }}</p>
    @endif
</x-ui.card>
