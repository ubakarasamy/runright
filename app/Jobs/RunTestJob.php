<?php

namespace App\Jobs;

use App\Models\TestRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

        // 1️⃣ Running
        $run->update([
            'status' => 'running',
            'result' => null,
        ]);

        // 2️⃣ Simulate execution
        sleep(5);

        // 🔴 Simulated test outcome
        $passed = random_int(0, 1) === 1;

        // 3️⃣ Finished
        $run->update([
            'status' => 'finished',
            'result' => $passed ? 'passed' : 'failed',
            'duration_ms' => 5000,
        ]);
    }
}
