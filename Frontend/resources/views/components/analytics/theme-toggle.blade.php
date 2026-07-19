<x-ui.button
    variant="ghost"
    size="icon-lg"
    type="button"
    data-ui="theme-toggle"
    x-data
    @click="$store.theme.toggle()"
    aria-label="Ganti tema warna"
    title="Ganti tema warna"
    {{ $attributes->except(['data-ui', 'aria-label', 'title', 'variant', 'size', 'type', 'href', 'as', 'x-data', '@click', 'x-on:click'])->twMerge('size-10 text-muted-foreground hover:text-foreground') }}
>
    <x-lucide-sun class="size-[18px] dark:hidden" />
    <x-lucide-moon class="hidden size-[18px] dark:block" />
</x-ui.button>
