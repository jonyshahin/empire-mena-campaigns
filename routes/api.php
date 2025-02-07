<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CampaignController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\OutletController;
use App\Http\Controllers\API\CompetitorBrandController;
use App\Http\Controllers\API\ConsumerController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\DistrictController;
use App\Http\Controllers\API\IncentiveController;
use App\Http\Controllers\API\IndustryController;
use App\Http\Controllers\API\NationalityController;
use App\Http\Controllers\API\ProductCategoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\PromoterController;
use App\Http\Controllers\API\PromoterTrackingController;
use App\Http\Controllers\API\RefusedReasonController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\TeamLeaderController;
use App\Http\Controllers\API\ZoneController;
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

Route::prefix('company')->group(function () {
    Route::get('/', [ClientController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [ClientController::class, 'store']);
            Route::post('show', [ClientController::class, 'show']);
            Route::post('update', [ClientController::class, 'update']);
            Route::post('delete', [ClientController::class, 'destroy']);
        });
    });
});

Route::prefix('product-category')->group(function () {
    Route::get('/', [ProductCategoryController::class, 'get_all']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [ProductCategoryController::class, 'store']);
            Route::post('show', [ProductCategoryController::class, 'show']);
            Route::post('update', [ProductCategoryController::class, 'update']);
            Route::post('delete', [ProductCategoryController::class, 'destroy']);
            Route::post('toggle-active', [ProductCategoryController::class, 'toggle_active']);
        });
    });
});

Route::prefix('outlet')->group(function () {
    Route::get('/', [OutletController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [OutletController::class, 'store']);
            Route::post('show', [OutletController::class, 'show']);
            Route::post('update', [OutletController::class, 'update']);
            Route::post('delete', [OutletController::class, 'destroy']);
        });
    });
});

Route::post('/login/promoter', [AuthController::class, 'login_promoter']);
Route::post('/login/admin', [AuthController::class, 'login_admin']);
Route::post('/login/client', [AuthController::class, 'login_client']);
Route::post('/login/team-leader', [AuthController::class, 'login_team_leader']);

Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::get('/verify-token', [AuthController::class, 'verifyToken']);
    Route::post('/set-campaign', [AuthController::class, 'setCampaign']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::middleware('role:admin')->group(function () {
        Route::get('get-profile', [AuthController::class, 'get_profile']);
        Route::post('update-profile', [AuthController::class, 'update_profile']);
        Route::post('change-password', [AuthController::class, 'change_password']);
    });
});

Route::prefix('competitor-brand')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [CompetitorBrandController::class, 'index']);
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [CompetitorBrandController::class, 'create_competitor_brand']);
            Route::post('show', [CompetitorBrandController::class, 'get_brand']);
            Route::post('update', [CompetitorBrandController::class, 'update_brand']);
            Route::post('delete', [CompetitorBrandController::class, 'delete_brand']);
        });
    });
});

Route::prefix('refused-reason')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [RefusedReasonController::class, 'index']);
    });
});

Route::prefix('consumer')->group(function () {
    Route::post('send-otp', [ConsumerController::class, 'send_otp']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [ConsumerController::class, 'index']);
        Route::post('create', [ConsumerController::class, 'store']);
        Route::post('show', [ConsumerController::class, 'show']);
        Route::post('update', [ConsumerController::class, 'update']);
        Route::post('delete', [ConsumerController::class, 'destroy']);
        Route::middleware('role:admin|team_leader')->group(function () {
            Route::get('report', [ConsumerController::class, 'report']);
            Route::get('consumers-by-promoter', [ConsumerController::class, 'consumersByPromoter']);
            Route::get('export', [ConsumerController::class, 'export']);
            Route::get('export-consumers-by-promoter', [ConsumerController::class, 'exportConsumersByPromoter']);
            Route::get('promoters-count-by-day', [ConsumerController::class, 'promotersCountByDay']);
            Route::get('export-promoters-count-by-day', [ConsumerController::class, 'exportPromotersCountByDay']);
            Route::get('promoter-daily-feedback', [ConsumerController::class, 'promoterDailyFeedback']);
            Route::get('export-promoter-daily-feedback', [ConsumerController::class, 'exportPromoterDailyFeedback']);
        });
    });
});

Route::prefix('promoter')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin|team_leader')->group(function () {
            Route::get('/', [PromoterController::class, 'index']);
            Route::post('create', [PromoterController::class, 'store']);
            Route::post('show', [PromoterController::class, 'show']);
            Route::post('update', [PromoterController::class, 'update']);
        });
    });
});

Route::prefix('team-leader')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::get('/', [TeamLeaderController::class, 'index']);
            Route::post('create', [TeamLeaderController::class, 'store']);
            Route::post('show', [TeamLeaderController::class, 'show']);
            Route::post('update', [TeamLeaderController::class, 'update']);
            Route::post('delete', [TeamLeaderController::class, 'destroy']);
        });
    });
});

Route::prefix('client')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('/', [ClientController::class, 'get_clients']);
            Route::post('create', [ClientController::class, 'store_client']);
            Route::post('show', [ClientController::class, 'show_client']);
            Route::post('update', [ClientController::class, 'update_client']);
            Route::post('delete', [ClientController::class, 'destroy_client']);
        });
    });
});

Route::prefix('district')->group(function () {
    Route::get('/', [DistrictController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [DistrictController::class, 'store']);
            Route::post('show', [DistrictController::class, 'show']);
            Route::post('update', [DistrictController::class, 'update']);
            Route::post('delete', [DistrictController::class, 'destroy']);
        });
    });
});

Route::prefix('incentive')->group(function () {
    Route::get('/', [IncentiveController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [IncentiveController::class, 'store']);
            Route::post('show', [IncentiveController::class, 'show']);
            Route::post('update', [IncentiveController::class, 'update']);
            Route::post('delete', [IncentiveController::class, 'destroy']);
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

Route::prefix('promoter-tracking')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('create', [PromoterTrackingController::class, 'store']);
        Route::middleware('role:admin')->group(function () {
            Route::get('/', [PromoterTrackingController::class, 'index']);
            Route::post('show', [PromoterTrackingController::class, 'show']);
            Route::post('update', [PromoterTrackingController::class, 'update']);
            Route::post('delete', [PromoterTrackingController::class, 'destroy']);
        });
        Route::middleware('role:team_leader')->group(function () {
            Route::get('/campaign', [PromoterTrackingController::class, 'campaign_promoter_tracking']);
        });
    });
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [ProductController::class, 'store']);
            Route::post('show', [ProductController::class, 'show']);
            Route::post('update', [ProductController::class, 'update']);
            Route::post('delete', [ProductController::class, 'destroy']);
        });
    });
});

Route::prefix('campaigns')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::get('/promoter', [CampaignController::class, 'promoter_campaigns']);
        Route::get('/team-leader', [CampaignController::class, 'team_leader_campaigns']);
        Route::get('/client', [CampaignController::class, 'client_campaigns']);
        Route::get('/team-leader', [CampaignController::class, 'team_leader_campaigns']);
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [CampaignController::class, 'store']);
            Route::post('update', [CampaignController::class, 'update']);
            Route::post('delete', [CampaignController::class, 'destroy']);
        });
        Route::middleware('role:client|admin')->group(function () {
            Route::post('show', [CampaignController::class, 'show']);
        });
    });
});

Route::prefix('industries')->group(function () {
    Route::get('/', [IndustryController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::post('create', [IndustryController::class, 'store']);
            Route::post('show', [IndustryController::class, 'show']);
            Route::post('update', [IndustryController::class, 'update']);
            Route::post('delete', [IndustryController::class, 'destroy']);
        });
    });
});

Route::prefix('dashboard')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:admin|client')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('show', [DashboardController::class, 'show']);
            Route::get('/sales-performance', [DashboardController::class, 'salesPerformance']);
            Route::get('/general-statistics', [DashboardController::class, 'generalStatistics']);
            Route::get('/trial-rate', [DashboardController::class, 'trialRate']);
            Route::get('/city-performance', [DashboardController::class, 'cityPerformance']);
        });
    });
});

Route::prefix('settings')->group(function () {
    Route::middleware('auth:sanctum')->group(
        function () {
            Route::middleware('role:admin')->group(function () {
                Route::get('/get', [SettingController::class, 'index']);
                Route::post('/set', [SettingController::class, 'set']);
                Route::post('/create', [SettingController::class, 'store']);
                Route::get('/show/{id}', [SettingController::class, 'show']);
                Route::post('/update/{id}', [SettingController::class, 'update']);
                Route::delete('/delete/{id}', [SettingController::class, 'delete']);
            });
        }
    );
});

Route::prefix('reports')->group(function () {
    Route::get('export-stock-campaign', [ReportController::class, 'exportStockCampaignReport']);
    Route::middleware('auth:sanctum')->group(
        function () {
            Route::middleware('role:admin')->group(function () {
                Route::get('stock-campaign', [ReportController::class, 'stockCampaignReport']);
                // Route::get('export-stock-campaign', [ReportController::class, 'exportStockCampaignReport']);
            });
        }
    );
});
