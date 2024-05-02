<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OutletController;
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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/verify-token', [AuthController::class, 'verifyToken']);
    Route::get('/logout', [AuthController::class, 'logout']);
});
