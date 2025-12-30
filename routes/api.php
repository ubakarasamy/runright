<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestRunController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api.token')->group(function () {

    Route::post('/test-runs', [TestRunController::class, 'store']);
    Route::get('/test-runs/{id}', [TestRunController::class, 'show']);
    Route::get('/test-runs', [TestRunController::class, 'index']);

});
