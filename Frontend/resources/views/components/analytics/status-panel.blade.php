@props(['status'])

@php($panelClasses = 'border-border bg-card text-foreground shadow-none [&>svg]:text-muted-foreground *:data-[slot=alert-description]:text-muted-foreground')

@if ($status === 'unavailable')
    <x-ui.alert tone="danger" data-ui="status-panel" {{ $attributes->twMerge($panelClasses) }}>
        <x-lucide-server-off />
        <x-ui.alert-title>Server analisis tidak aktif.</x-ui.alert-title>
        <x-ui.alert-description>Jalankan: uvicorn api:app --port 8000 di folder Backend.</x-ui.alert-description>
    </x-ui.alert>
@elseif ($status === 'no_data')
    <x-ui.alert tone="warning" data-ui="status-panel" {{ $attributes->twMerge($panelClasses) }}>
        <x-lucide-triangle-alert />
        <x-ui.alert-title>Belum ada data snapshot.</x-ui.alert-title>
        <x-ui.alert-description>Jalankan ingest dulu.</x-ui.alert-description>
    </x-ui.alert>
@elseif ($status === 'error')
    <x-ui.alert tone="danger" data-ui="status-panel" {{ $attributes->twMerge($panelClasses) }}>
        <x-lucide-circle-alert />
        <x-ui.alert-title>Server analisis mengembalikan error.</x-ui.alert-title>
        <x-ui.alert-description>Cek log uvicorn di folder Backend.</x-ui.alert-description>
    </x-ui.alert>
@endif
