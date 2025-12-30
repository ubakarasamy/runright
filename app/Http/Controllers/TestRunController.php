<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        /** @var \App\Models\ApiToken $token */
        $token = $request->attributes->get('api_token');

        if (!$token || !$token->company) {
            return response()->json([
                'message' => 'Invalid API token'
            ], 401);
        }

        $request->validate([
            'project_id' => [
                'required',
                Rule::exists('projects', 'id')
                    ->where('company_id', $token->company_id),
            ],
        ]);

        $run = TestRun::create([
            'company_id' => $token->company_id,
            'project_id' => $request->project_id,
            'source' => 'cli',
            'status' => 'queued',
        ]);

        RunTestJob::dispatch($run->id);

        return response()->json([
            'test_run_id' => $run->id,
            'status' => $run->status,
        ]);
    }
    
    public function show(Request $request, int $id)
    {
        /** @var \App\Models\ApiToken $token */
        $token = $request->attributes->get('api_token');

        if (!$token || !$token->company) {
            return response()->json([
                'message' => 'Invalid API token'
            ], 401);
        }

        $run = TestRun::where('id', $id)
            ->where('company_id', $token->company_id)
            ->first();

        if (!$run) {
            return response()->json([
                'message' => 'Test run not found'
            ], 404);
        }

        return response()->json([
            'id'          => $run->id,
            'status'      => $run->status,
            'result'      => $run->result,
            'duration_ms'=> $run->duration_ms,
        ]);
    }


}
