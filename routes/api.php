<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\NormalAdsController;
use App\Http\Controllers\Api\V1\UniqueAdsController;
use App\Http\Controllers\Api\V1\CaishhaAdsController;

Route::prefix('v1')->group(function () {
    Route::apiResource('normal-ads', NormalAdsController::class);
    Route::post('normal-ads/{ad}/actions/republish', [NormalAdsController::class, 'republish']);

    Route::apiResource('unique-ads', UniqueAdsController::class);
    Route::apiResource('caishha-ads', CaishhaAdsController::class);
});
