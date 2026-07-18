<x-ui.button variant="ghost" size="icon" x-data @click="$store.theme.toggle()" aria-label="Ganti tema warna" title="Ganti tema warna">
    <x-lucide-sun class="dark:hidden" />
    <x-lucide-moon class="hidden dark:block" />
</x-ui.button>
