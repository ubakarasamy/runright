<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TestRun;
use App\Jobs\RunTestJob;


class TestRunController extends Controller
{

    public function index(Request $request)
    {
        return TestRun::where('company_id', $request->company_id)
            ->latest()
            ->limit(20)
            ->get(['id', 'status', 'created_at', 'duration_ms']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $run = TestRun::create([
            'company_id' => $request->company_id,
            'project_id' => $request->project_id,
            'source' => 'cli',
            'status' => 'queued',
        ]);

        // 🚀 Dispatch runner job
        RunTestJob::dispatch($run->id);

        return response()->json([
            'test_run_id' => $run->id,
            'status' => $run->status,
        ]);
    }
    
    public function show(Request $request, int $id)
    {
        $run = TestRun::where('id', $id)
            ->where('company_id', $request->company_id)
            ->firstOrFail();

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'result' => $run->result, // 👈 key addition
            'duration_ms' => $run->duration_ms,
        ]);
    }


}
