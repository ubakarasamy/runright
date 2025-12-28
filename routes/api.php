<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\ApiTokenAuth;
use App\Http\Controllers\TestRunController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/test-runs', [TestRunController::class, 'store'])
    ->middleware(ApiTokenAuth::class);

Route::middleware(ApiTokenAuth::class)->group(function () {
    Route::post('/test-runs', [TestRunController::class, 'store']);
    Route::get('/test-runs/{id}', [TestRunController::class, 'show']);
});

Route::get('/test-runs', [TestRunController::class, 'index']);

