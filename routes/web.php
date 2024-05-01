<?php

use App\Http\Controllers\CompetitorBrandController;
use App\Http\Controllers\ConsumerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PromoterController;
use App\Http\Controllers\RefusedReasonController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'role:super-admin'])->group(function () {
    Route::prefix('promoter')->group(function () {
        Route::get('/get', [PromoterController::class, 'index'])->name('promoter');
        Route::get('/create', [PromoterController::class, 'create'])->name('promoter.create');
        Route::post('/store', [PromoterController::class, 'store'])->name('promoter.store');
    });
    Route::prefix('competitor')->group(function () {
        Route::get('/get', [CompetitorBrandController::class, 'index'])->name('competitor');
        Route::get('/create', [CompetitorBrandController::class, 'create'])->name('competitor.create');
        Route::post('/store', [CompetitorBrandController::class, 'store'])->name('competitor.store');
    });
    Route::prefix('refused-reason')->group(function () {
        Route::get('/get', [RefusedReasonController::class, 'index'])->name('refusedreason');
        Route::get('/create', [RefusedReasonController::class, 'create'])->name('refusedreason.create');
        Route::post('/store', [RefusedReasonController::class, 'store'])->name('refusedreason.store');
    });
});

Route::prefix('consumer')->group(function () {
    Route::get('/get', [ConsumerController::class, 'index'])->name('consumer');
    Route::get('/create', [ConsumerController::class, 'create'])->name('consumer.create');
    Route::post('/store', [ConsumerController::class, 'store'])->name('consumer.store');
});
