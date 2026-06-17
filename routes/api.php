<?php

use App\Http\Controllers\Api\v1\Server;
use App\Http\Controllers\Api\v1\Admin;
use App\Http\Controllers\Api\v1\User;
use App\Http\Controllers\Api\v1\Guest;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::post('/login', [Admin\AuthController::class, 'login']);
        Route::middleware(['auth:sanctum', 'can:access-admin', 'check.status'])->group(function () {
            Route::prefix('notifications')->group(function () {
                Route::post('/send-all', [Admin\NotificationController::class, 'sendToAll']);
            });
            Route::prefix('users')->group(function () {
                Route::get('/', [Admin\UserController::class, 'index']);
                Route::get('/{user}', [Admin\UserController::class, 'show']);
                Route::delete('/{user}', [Admin\UserController::class, 'destroy']);
                Route::post('/{user}/change-user', [Admin\UserController::class, 'changeUser']);
                Route::post('/{user}/block', [Admin\UserController::class, 'block']);
                Route::post('/{user}/unblock', [Admin\UserController::class, 'unblock']);
                Route::patch('/{user}/role', [Admin\UserController::class, 'changeRole']);
                Route::post('/{user}/restore', [Admin\UserController::class, 'restore'])->withTrashed();
                Route::delete('/{user}/force-delete', [Admin\UserController::class, 'forceDelete'])->withTrashed();
            });
        });
    });


    Route::middleware(['auth:sanctum', 'check.status'])->group(function () {
        Route::prefix('profile')->group(function () {
            Route::get('me', [User\ProfileController::class, 'me']);
            Route::post('update', [User\ProfileController::class, 'update']);
        });
        Route::prefix('captain')->middleware(['can:captain-leader'])->group(function () {

        });
    });
    Route::group([], function () {
        Route::prefix('auth')->group(function () {
            Route::post('/register', [Guest\AuthController::class, 'register']);
            Route::post('/login', [Guest\AuthController::class, 'login']);
        });

        // ----------------------------------------------------------------//
        //                        COUNTRY METHODS                          //
        // ----------------------------------------------------------------//
        Route::prefix('regions')->group(function () {
            Route::post('countries', [Server\RegionController::class, 'getCountries']);
            Route::post('cities', [Server\RegionController::class, 'getCities']);
        });
    });
});
