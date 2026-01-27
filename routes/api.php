<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\NormalAdsController;
use App\Http\Controllers\Api\V1\UniqueAdsController;
use App\Http\Controllers\Api\V1\CaishhaAdsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;

Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Users (admin only for create)
    Route::post('users', [UserController::class, 'store'])->middleware('auth:sanctum');

    Route::apiResource('normal-ads', NormalAdsController::class);
    Route::post('normal-ads/{ad}/actions/republish', [NormalAdsController::class, 'republish']);

    Route::apiResource('unique-ads', UniqueAdsController::class);
    Route::apiResource('caishha-ads', CaishhaAdsController::class);
});
