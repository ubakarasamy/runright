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

        $run->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            // Simulated execution (replace later)
            sleep(5);

            $passed = random_int(0, 1) === 1;

            if (!$passed) {
                throw new \Exception('Health check failed');
            }

            $run->update([
                'status' => 'finished',
                'result' => 'passed',
                'finished_at' => now(),
                'duration_ms' => 5000,
                'logs' => [
                    ['step' => 'health_check', 'status' => 'ok']
                ],
            ]);

        } catch (\Throwable $e) {

            $run->update([
                'status' => 'finished',
                'result' => 'failed',
                'finished_at' => now(),
                'duration_ms' => 5000,
                'error_message' => $e->getMessage(),
                'logs' => [
                    [
                        'step' => 'health_check',
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ]
                ],
            ]);
        }
    }
}
