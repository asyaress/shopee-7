<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Mobile\CeoController as MobileCeoController;
use App\Http\Controllers\Api\V1\Mobile\AuthController as MobileAuthController;
use App\Http\Controllers\Api\V1\Mobile\DeviceController as MobileDeviceController;
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

    Route::prefix('mobile')->group(function () {
        Route::post('/auth/login', [MobileAuthController::class, 'login'])->middleware('throttle:mobile-login-api');

        Route::middleware(['auth:sanctum', 'abilities:mobile:ceo', 'mobile.ceo'])->group(function () {
            Route::post('/auth/logout', [MobileAuthController::class, 'logout']);
            Route::get('/auth/me', [MobileAuthController::class, 'me']);
            Route::post('/devices/register', [MobileDeviceController::class, 'register'])->middleware('throttle:mobile-write-api');

            Route::get('/ceo/shops', [MobileCeoController::class, 'shops']);
            Route::post('/ceo/shops/active', [MobileCeoController::class, 'setActiveShop'])->middleware('throttle:mobile-write-api');
            Route::get('/ceo/dashboard', [MobileCeoController::class, 'dashboard']);
            Route::get('/ceo/rekap', [MobileCeoController::class, 'rekap']);
            Route::get('/ceo/targets', [MobileCeoController::class, 'targets']);
            Route::post('/ceo/targets', [MobileCeoController::class, 'saveTargets'])->middleware('throttle:mobile-write-api');
            Route::get('/ceo/hpp/priority', [MobileCeoController::class, 'hppPriority']);
            Route::post('/ceo/hpp/bulk', [MobileCeoController::class, 'saveHppBulk'])->middleware('throttle:mobile-write-api');
            Route::get('/ceo/alerts', [MobileCeoController::class, 'alerts']);
            Route::post('/ceo/alerts/read', [MobileCeoController::class, 'markAlertsRead'])->middleware('throttle:mobile-write-api');
            Route::get('/ceo/decisions', [MobileCeoController::class, 'decisions']);
            Route::post('/ceo/decisions', [MobileCeoController::class, 'storeDecision'])->middleware('throttle:mobile-write-api');
        });
    });

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
