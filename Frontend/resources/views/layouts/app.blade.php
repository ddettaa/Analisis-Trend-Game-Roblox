<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="w-60 shrink-0 border-r border-border bg-card flex flex-col">
        <div class="p-4 font-semibold text-lg">Analisis Trend Roblox</div>
        <nav class="flex-1 px-2 space-y-1">
            <a href="/dashboard" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm {{ request()->is('dashboard') ? 'bg-accent text-accent-foreground font-medium' : 'text-muted-foreground hover:bg-accent/50' }}">
                <x-lucide-bar-chart-3 class="size-4" /> Ringkasan
            </a>
            <a href="/dashboard/saturasi" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm {{ request()->is('dashboard/saturasi') ? 'bg-accent text-accent-foreground font-medium' : 'text-muted-foreground hover:bg-accent/50' }}">
                <x-lucide-gauge class="size-4" /> Saturasi
            </a>
            <a href="/dashboard/viral" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm {{ request()->is('dashboard/viral') ? 'bg-accent text-accent-foreground font-medium' : 'text-muted-foreground hover:bg-accent/50' }}">
                <x-lucide-flame class="size-4" /> Viral Muda
            </a>
        </nav>
        <div class="p-4 border-t border-border">
            <button type="button" x-data
                @click="$store.theme.toggle()"
                class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                <x-lucide-sun-moon class="size-4" /> Ganti Tema
            </button>
        </div>
    </aside>

    {{-- Konten --}}
    <main class="flex-1 p-6 space-y-4 overflow-x-hidden">
        @if ($status === 'unavailable')
            <div class="rounded-lg border border-destructive/50 bg-destructive/10 text-destructive px-4 py-3 text-sm">
                Server analisis tidak aktif. Jalankan: <code>uvicorn api:app --port 8000</code> di folder Backend.
            </div>
        @elseif ($status === 'no_data')
            <div class="rounded-lg border border-yellow-500/50 bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 px-4 py-3 text-sm">
                Belum ada data snapshot. Jalankan ingest dulu.
            </div>
        @elseif ($status === 'error')
            <div class="rounded-lg border border-destructive/50 bg-destructive/10 text-destructive px-4 py-3 text-sm">
                Server analisis mengembalikan error. Cek log uvicorn di folder Backend.
            </div>
        @elseif ($snapshot)
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <x-ui.badge variant="secondary">Snapshot #{{ $snapshot['snapshot_id'] }}</x-ui.badge>
                <span>{{ $snapshot['game_count'] }} game</span>
                <span>·</span>
                <span>{{ $snapshot['taken_at'] }}</span>
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
