<?php

namespace App\Jobs;

use App\Models\TestRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
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
        $run = TestRun::with('project')->find($this->testRunId);

        if (!$run || !$run->project) {
            return;
        }

        $start = now();
        $logs = [];

        try {
            // 1️⃣ Mark as running
            $run->update([
                'status' => 'running',
                'started_at' => $start,
            ]);

            // 2️⃣ Load test definition from file
            $path = base_path("tests/runright/project_{$run->project_id}.json");

            if (!File::exists($path)) {
                throw new \Exception("Test definition file not found: project_{$run->project_id}.json");
            }

            $definition = json_decode(File::get($path), true);

            if (!$definition || !isset($definition['steps']) || !is_array($definition['steps'])) {
                throw new \Exception("Invalid test definition format");
            }

            // 3️⃣ Execute steps sequentially
            foreach ($definition['steps'] as $step) {

                $method = strtoupper($step['request']['method'] ?? 'GET');
                $url = $run->project->base_url . ($step['request']['url'] ?? '/');

                $response = Http::timeout(10)->send($method, $url);

                // Log request
                $stepLog = [
                    'step' => $step['name'] ?? "{$method} {$url}",
                    'method' => $method,
                    'url' => $url,
                    'http_status' => $response->status(),
                ];

                // 4️⃣ Status assertion
                if (isset($step['expect']['status']) &&
                    $response->status() !== $step['expect']['status']) {

                    throw new \Exception(
                        "{$stepLog['step']} failed: expected {$step['expect']['status']}, got {$response->status()}"
                    );
                }

                // 5️⃣ JSON assertions (optional)
                if (isset($step['expect']['json'])) {
                    foreach ($step['expect']['json'] as $key => $expected) {
                        $actual = data_get($response->json(), $key);
                        if ($actual !== $expected) {
                            throw new \Exception(
                                "{$stepLog['step']} JSON assertion failed on '{$key}'"
                            );
                        }
                    }
                }

                $stepLog['status'] = 'passed';
                $logs[] = $stepLog;
            }

            // 6️⃣ All steps passed
            $run->update([
                'status' => 'finished',
                'result' => 'passed',
                'finished_at' => now(),
                'duration_ms' => now()->diffInMilliseconds($start),
                'logs' => $logs,
            ]);

        } catch (\Throwable $e) {

            // Log failure
            $logs[] = [
                'step' => $step['name'] ?? 'unknown',
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];

            $run->update([
                'status' => 'finished',
                'result' => 'failed',
                'finished_at' => now(),
                'duration_ms' => now()->diffInMilliseconds($start),
                'error_message' => $e->getMessage(),
                'logs' => $logs,
            ]);
        }
    }
}
