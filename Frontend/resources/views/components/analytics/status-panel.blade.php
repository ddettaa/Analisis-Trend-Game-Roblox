@props(['status'])

@if ($status === 'unavailable')
    <x-ui.alert tone="danger">
        <x-lucide-server-off />
        <x-ui.alert-title>Server analisis tidak aktif.</x-ui.alert-title>
        <x-ui.alert-description>Jalankan: uvicorn api:app --port 8000 di folder Backend.</x-ui.alert-description>
    </x-ui.alert>
@elseif ($status === 'no_data')
    <x-ui.alert tone="warning">
        <x-lucide-triangle-alert />
        <x-ui.alert-title>Belum ada data snapshot.</x-ui.alert-title>
        <x-ui.alert-description>Jalankan ingest dulu.</x-ui.alert-description>
    </x-ui.alert>
@elseif ($status === 'error')
    <x-ui.alert tone="danger">
        <x-lucide-circle-alert />
        <x-ui.alert-title>Server analisis mengembalikan error.</x-ui.alert-title>
        <x-ui.alert-description>Cek log uvicorn di folder Backend.</x-ui.alert-description>
    </x-ui.alert>
@endif
