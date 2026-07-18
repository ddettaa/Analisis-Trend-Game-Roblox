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
@php
    $workspaceContext = request()->is('dashboard/saturasi')
        ? 'Market saturation'
        : (request()->is('dashboard/viral') ? 'Young momentum' : 'Market overview');
@endphp
<body data-shell="saas-dashboard" class="min-h-screen bg-background text-foreground antialiased">
    <header data-ui="command-bar" class="sticky top-0 z-50 h-14 border-b border-border bg-background/90 backdrop-blur-md">
        <div class="flex h-full w-full items-center gap-3 px-4 lg:px-5">
            <x-analytics.brand-mark class="shrink-0" />
            <span aria-hidden="true" class="hidden h-5 w-px bg-border sm:block"></span>
            <div class="hidden min-w-0 items-center gap-2 sm:flex">
                <span class="saas-label">Workspace</span>
                <span class="truncate text-sm font-medium text-foreground">{{ $workspaceContext }}</span>
            </div>
            <div class="ml-auto flex shrink-0 items-center gap-1">
                <a href="/" class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground outline-none transition-colors hover:bg-accent/60 hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring">
                    Beranda
                </a>
                <x-analytics.theme-toggle />
            </div>
        </div>
    </header>

    <x-analytics.dashboard-nav />

    <main class="lg:pl-16">
        <div class="saas-container space-y-5 py-5 pb-[calc(5rem+env(safe-area-inset-bottom))] lg:py-6 lg:pb-10">
            <x-analytics.status-panel :status="$status" />

            @if ($status === 'ok' && $snapshot)
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 font-mono text-[11px] tabular-nums text-muted-foreground">
                    <x-ui.badge variant="secondary" class="font-mono text-[10px] uppercase tracking-[0.06em]">Snapshot #{{ $snapshot['snapshot_id'] }}</x-ui.badge>
                    <span>{{ $snapshot['game_count'] }} game</span>
                    <span aria-hidden="true" class="text-border">&middot;</span>
                    <span>{{ $snapshot['taken_at'] }}</span>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</body>
</html>
