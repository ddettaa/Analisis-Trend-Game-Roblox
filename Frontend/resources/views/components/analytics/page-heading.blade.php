@props(['eyebrow', 'title', 'description' => null])

<header data-ui="page-heading" {{ $attributes->except('data-ui')->twMerge('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between') }}>
    <div class="min-w-0 space-y-2">
        <p class="saas-label editorial-eyebrow">{{ $eyebrow }}</p>
        <h1 class="saas-title">{{ $title }}</h1>
        @if ($description)
            <p class="max-w-2xl text-sm leading-6 text-muted-foreground">{{ $description }}</p>
        @endif
    </div>
    @isset($action)
        <div class="flex shrink-0 items-center gap-2">{{ $action }}</div>
    @endisset
</header>
