<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;

class DashboardController extends Controller
{
    public function ringkasan(FastApiClient $api)
    {
        $snapshot = $api->snapshot();
        $ranking = $api->genreRanking();
        return view('dashboard.ringkasan', [
            'snapshot' => $snapshot, 'status' => $api->status(), 'ranking' => $ranking,
        ]);
    }

    public function saturasi(FastApiClient $api)
    {
        $snapshot = $api->snapshot();
        $saturation = $api->genreSaturation();
        return view('dashboard.saturasi', [
            'snapshot' => $snapshot, 'status' => $api->status(), 'saturation' => $saturation,
        ]);
    }

    public function viral(FastApiClient $api)
    {
        $snapshot = $api->snapshot();
        $viral = $api->viralMuda(90);
        return view('dashboard.viral', [
            'snapshot' => $snapshot, 'status' => $api->status(), 'viral' => $viral,
        ]);
    }
}
