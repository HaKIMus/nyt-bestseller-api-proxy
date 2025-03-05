<?php

declare(strict_types=1);

use App\NewYorkTimes\UserInterface\Api\V1\NytController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:api'])->group(function () {
    Route::prefix('v1')->group(function () {
        Route::get('/bestsellers', [NytController::class, 'getBestSellers']);
    });
});