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
        $currentStep = 'initialization';

        try {
            // 1️⃣ Mark as running
            $run->update([
                'status' => 'running',
                'started_at' => $start,
            ]);

            // 2️⃣ Load test definition
            $path = base_path("tests/runright/project_{$run->project_id}.json");

            if (!File::exists($path)) {
                throw new \Exception("Test definition file not found: project_{$run->project_id}.json");
            }

            $definition = json_decode(File::get($path), true);

            if (!$definition || !isset($definition['steps']) || !is_array($definition['steps'])) {
                throw new \Exception("Invalid test definition format");
            }

            // 3️⃣ Execute steps
            foreach ($definition['steps'] as $step) {

                $currentStep = $step['name'] ?? 'unnamed_step';

                $method = strtoupper($step['request']['method'] ?? 'GET');
                $url = $run->project->base_url . ($step['request']['url'] ?? '/');

                $response = Http::timeout(10)->send($method, $url);

                // Status assertion
                if (isset($step['expect']['status']) &&
                    $response->status() !== $step['expect']['status']) {

                    throw new \Exception(
                        "{$currentStep} failed: expected {$step['expect']['status']}, got {$response->status()}"
                    );
                }

                // JSON assertions
                foreach ($step['expect']['json'] ?? [] as $key => $expected) {
                    if (data_get($response->json(), $key) !== $expected) {
                        throw new \Exception(
                            "{$currentStep} JSON assertion failed on '{$key}'"
                        );
                    }
                }

                $logs[] = [
                    'step' => $currentStep,
                    'status' => 'passed',
                    'http_status' => $response->status(),
                ];
            }

            // 4️⃣ All steps passed
            $run->update([
                'status' => 'finished',
                'result' => 'passed',
                'finished_at' => now(),
                'duration_ms' => now()->diffInMilliseconds($start),
                'logs' => $logs,
            ]);

        } catch (\Throwable $e) {

            // 🔒 ALWAYS safe
            $logs[] = [
                'step' => $currentStep,
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
