<?php

namespace App\Jobs;

use App\Models\TestRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class RunTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $testRunId;

    public function __construct(int $testRunId)
    {
        $this->testRunId = $testRunId;
    }

    public function handle(): void
    {
        $run = TestRun::find($this->testRunId);

        if (!$run) {
            return;
        }

        // 1️⃣ Mark running
        $run->update([
            'status' => 'running',
            'started_at' => now(),
            'result' => null,
            'error_message' => null,
            'logs' => [],
        ]);

        $startTime = microtime(true);
        $logs = [];

        try {
            /**
             * 🔹 STEP 1: HTTP GET
             */
            $response = Http::timeout(5)->get('http://example.com');

            $logs[] = [
                'step' => 'GET /',
                'status' => $response->status(),
            ];

            /**
             * 🔹 STEP 2: Expect status
             */
            if ($response->status() !== 200) {
                throw new \Exception(
                    'Expected status 200, got ' . $response->status()
                );
            }

            $logs[] = [
                'step' => 'expectStatus',
                'message' => '200 OK',
            ];

            /**
             * 🔹 STEP 3: Expect text
             */
            if (!str_contains($response->body(), 'Example Domain')) {
                throw new \Exception(
                    'Expected text "Example Domain" not found'
                );
            }

            $logs[] = [
                'step' => 'expectText',
                'message' => 'Example Domain found',
            ];

            /**
             * ✅ SUCCESS
             */
            $run->update([
                'status' => 'finished',
                'result' => 'passed',
                'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'finished_at' => now(),
                'logs' => $logs,
            ]);
        } catch (Throwable $e) {
            /**
             * ❌ FAILURE
             */
            $run->update([
                'status' => 'failed',
                'result' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
                'logs' => array_merge($logs, [
                    [
                        'step' => 'error',
                        'message' => $e->getMessage(),
                    ],
                ]),
            ]);

            throw $e; // keep Laravel logging intact
        }
    }
}
