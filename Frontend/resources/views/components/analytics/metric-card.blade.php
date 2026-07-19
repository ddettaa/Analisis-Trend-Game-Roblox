@props(['label', 'value', 'annotation' => null, 'trend' => null])

<div data-ui="metric-card" {{ $attributes->except('data-ui')->twMerge('saas-panel editorial-card flex min-h-32 flex-col p-4') }}>
    <p class="saas-label">{{ $label }}</p>
    <div class="mt-4 flex flex-wrap items-baseline justify-between gap-2">
        <p class="font-mono text-3xl font-semibold tabular-nums tracking-[-0.05em] text-foreground">{{ $value }}</p>
        @if (! is_null($trend))
            <span class="rounded-md border border-border bg-muted px-2 py-1 font-mono text-[11px] font-medium tabular-nums text-muted-foreground">{{ $trend }}</span>
        @endif
    </div>
    @if ($annotation)
        <p class="mt-auto pt-3 text-xs leading-5 text-muted-foreground">{{ $annotation }}</p>
    @endif
</div>
