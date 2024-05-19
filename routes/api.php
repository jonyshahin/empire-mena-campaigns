<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OutletController;
use App\Http\Controllers\API\CompetitorBrandController;
use App\Http\Controllers\API\ConsumerController;
use App\Http\Controllers\API\DistrictController;
use App\Http\Controllers\API\NationalityController;
use App\Http\Controllers\API\PromoterController;
use App\Http\Controllers\API\RefusedReasonController;
use App\Http\Controllers\API\ZoneController;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::prefix('outlet')->group(function () {
    Route::get('/', [OutletController::class, 'index']);
});

Route::post('/login', [AuthController::class, 'login']);

Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::get('/verify-token', [AuthController::class, 'verifyToken']);
    Route::get('/logout', [AuthController::class, 'logout']);
});

Route::prefix('competitor-brand')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [CompetitorBrandController::class, 'index']);
    });
});

Route::prefix('refused-reason')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [RefusedReasonController::class, 'index']);
    });
});

Route::prefix('consumer')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [ConsumerController::class, 'index']);
        Route::post('create', [ConsumerController::class, 'store']);
        Route::post('show', [ConsumerController::class, 'show']);
        Route::post('update', [ConsumerController::class, 'update']);
        Route::post('delete', [ConsumerController::class, 'destroy']);
        Route::middleware('role:admin')->group(function () {
            Route::get('report', [ConsumerController::class, 'report']);
            Route::get('consumers-by-promoter', [ConsumerController::class, 'consumersByPromoter']);
        });
    });
});

Route::get('export', [ConsumerController::class, 'export']);
Route::get('export-consumers-by-promoter', [ConsumerController::class, 'exportConsumersByPromoter']);

Route::prefix('promoter')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::get('/', [PromoterController::class, 'index']);
            Route::post('create', [PromoterController::class, 'store']);
            Route::post('show', [PromoterController::class, 'show']);
            Route::post('update', [PromoterController::class, 'update']);
        });
    });
});

Route::prefix('district')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::get('/', [DistrictController::class, 'index']);
            Route::post('create', [DistrictController::class, 'store']);
            Route::post('show', [DistrictController::class, 'show']);
            Route::post('update', [DistrictController::class, 'update']);
            Route::post('delete', [DistrictController::class, 'destroy']);
        });
    });
});

Route::prefix('nationality')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [NationalityController::class, 'index']);
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [NationalityController::class, 'store']);
            Route::post('show', [NationalityController::class, 'show']);
            Route::post('update', [NationalityController::class, 'update']);
            Route::post('delete', [NationalityController::class, 'destroy']);
        });
    });
});

Route::prefix('zone')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::get('/', [ZoneController::class, 'index']);
            Route::post('create', [ZoneController::class, 'store']);
            Route::post('show', [ZoneController::class, 'show']);
            Route::post('update', [ZoneController::class, 'update']);
            Route::post('delete', [ZoneController::class, 'destroy']);
        });
    });
});

Route::prefix('outlet')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [OutletController::class, 'store']);
            Route::post('show', [OutletController::class, 'show']);
            Route::post('update', [OutletController::class, 'update']);
            Route::post('delete', [OutletController::class, 'destroy']);
        });
    });
});
