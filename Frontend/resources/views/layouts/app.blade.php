<!DOCTYPE html>
<html id="app" lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    <script>
        try { const m = localStorage.getItem('theme:mode'); if (m === 'dark' || ((!m || m === 'system') && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark'); } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-shell="editorial-dashboard" class="min-h-screen bg-background text-foreground antialiased">
    <header class="sticky top-0 z-50 flex h-14 items-center justify-between border-b border-border bg-background/95 px-4 backdrop-blur lg:px-6">
        <x-analytics.brand-mark />
        <div class="flex items-center gap-2">
            <a href="/" class="text-sm text-muted-foreground hover:text-foreground">Landing</a>
            <x-analytics.theme-toggle />
        </div>
    </header>

    <x-analytics.dashboard-nav />

    <main class="space-y-4 px-4 py-6 pb-[calc(5rem+env(safe-area-inset-bottom))] lg:pb-10 lg:pl-32 lg:pr-8">
        <x-analytics.status-panel :status="$status" />

        @if ($status === 'ok' && $snapshot)
            <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                <x-ui.badge variant="secondary">Snapshot #{{ $snapshot['snapshot_id'] }}</x-ui.badge>
                <span>{{ $snapshot['game_count'] }} game</span>
                <span aria-hidden="true">·</span>
                <span>{{ $snapshot['taken_at'] }}</span>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
