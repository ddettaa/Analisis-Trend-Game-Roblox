@props(['eyebrow', 'title', 'description' => null])

<header {{ $attributes->twMerge('space-y-2') }}>
    <p class="editorial-eyebrow">{{ $eyebrow }}</p>
    <h1 class="workspace-title">{{ $title }}</h1>
    @if ($description)
        <p class="max-w-2xl text-sm leading-6 text-muted-foreground">{{ $description }}</p>
    @endif
</header>
