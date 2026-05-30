<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CheckinController;
use App\Http\Controllers\Api\V1\GuestController;
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\Internal\MaintenanceController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::get('/health', [SystemController::class, 'health'])->middleware('throttle:health-api');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login-api');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::post('/checkins/sync', [CheckinController::class, 'sync'])->middleware('throttle:sync-api');
        Route::get('/checkins', [CheckinController::class, 'index'])->middleware('throttle:sync-api');
        Route::get('/checkins/{guestCheckin}', [CheckinController::class, 'show'])->middleware('throttle:sync-api');
        Route::get('/checkins/{guestCheckin}/signature', [CheckinController::class, 'signature'])
            ->middleware('throttle:sync-api')
            ->name('api.v1.checkins.signature');

        Route::get('/guests', [GuestController::class, 'index'])->middleware('throttle:sync-api');
        Route::get('/guests/search', [GuestController::class, 'search'])->middleware('throttle:sync-api');
        Route::post('/guests', [GuestController::class, 'store'])->middleware('throttle:sync-api');
    });
});

Route::prefix('internal')->group(function () {
    Route::get('/maintenance/run', [MaintenanceController::class, 'run'])->middleware('throttle:maintenance-api');
});
