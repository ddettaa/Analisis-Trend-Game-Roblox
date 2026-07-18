<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class FastApiClient
{
    private string $base;
    private string $status = 'ok';

    public function __construct()
    {
        $this->base = rtrim(config('services.fastapi.url', 'http://127.0.0.1:8000'), '/');
    }

    public function status(): string
    {
        return $this->status;
    }

    private function get(string $path): ?array
    {
        try {
            $res = Http::timeout(5)->get($this->base . $path);
        } catch (ConnectionException $e) {
            $this->status = 'unavailable';
            return null;
        }
        if ($res->status() === 404) {
            $this->status = 'no_data';
            return null;
        }
        if (! $res->successful()) {
            $this->status = 'error';
            return null;
        }
        return $res->json();
    }

    public function snapshot(): ?array
    {
        return $this->get('/api/snapshot');
    }

    public function genreRanking(): array
    {
        return $this->get('/api/genre/ranking') ?? ['ranking' => [], 'share' => []];
    }

    public function genreSaturation(): array
    {
        return $this->get('/api/genre/saturation') ?? ['data' => []];
    }

    public function viralMuda(int $maxUmur = 90): array
    {
        return $this->get('/api/viral-muda?max_umur=' . $maxUmur) ?? ['max_umur' => $maxUmur, 'data' => []];
    }
}
