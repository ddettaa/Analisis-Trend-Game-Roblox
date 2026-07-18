<?php

namespace Tests\Feature;

use App\Services\FastApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FastApiClientTest extends TestCase
{
    public function test_snapshot_parses_json(): void
    {
        Http::fake(['*/api/snapshot' => Http::response([
            'snapshot_id' => 3, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781,
        ], 200)]);
        $client = new FastApiClient();
        $this->assertSame(781, $client->snapshot()['game_count']);
    }

    public function test_404_marks_no_data(): void
    {
        Http::fake(['*/api/snapshot' => Http::response(['detail' => 'x'], 404)]);
        $client = new FastApiClient();
        $this->assertNull($client->snapshot());
        $this->assertSame('no_data', $client->status());
    }

    public function test_connection_error_marks_unavailable(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('refused');
        });
        $client = new FastApiClient();
        $this->assertNull($client->snapshot());
        $this->assertSame('unavailable', $client->status());
    }
}
