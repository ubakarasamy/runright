<?php

namespace App\Jobs;

use App\Models\TestRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

        // 1️⃣ Mark as running
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
            // 2️⃣ Simulate execution (replace later with real runner)
            sleep(5);

            $passed = random_int(0, 1) === 1;

            $logs[] = [
                'step' => 'simulation',
                'message' => $passed ? 'Test passed' : 'Test failed',
            ];

            // 3️⃣ Success / Failure result
            $run->update([
                'status' => 'finished',
                'result' => $passed ? 'passed' : 'failed',
                'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'finished_at' => now(),
                'logs' => $logs,
            ]);
        } catch (Throwable $e) {
            // 4️⃣ Hard failure (exception)
            $run->update([
                'status' => 'failed',
                'result' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
                'logs' => array_merge($logs, [
                    [
                        'step' => 'exception',
                        'message' => $e->getMessage(),
                    ],
                ]),
            ]);

            throw $e; // let Laravel log it
        }
    }
}
