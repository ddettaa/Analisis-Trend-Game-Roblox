@props(['compact' => false])

<a
    href="/"
    data-ui="brand-mark"
    aria-label="Roblox Signals — beranda"
    {{ $attributes->except(['data-ui', 'href', 'aria-label'])->twMerge('inline-flex min-w-0 items-center gap-2.5 rounded-md text-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background') }}
>
    <span aria-hidden="true" class="grid size-8 shrink-0 place-items-center rounded-lg border border-border bg-card font-mono text-[10px] font-semibold tracking-[-0.04em] shadow-xs">RS</span>
    <span class="{{ $compact ? 'sr-only' : 'truncate text-sm font-semibold tracking-[-0.02em]' }}">Roblox Signals</span>
</a>
