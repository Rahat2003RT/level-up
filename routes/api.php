<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\User;
use App\Http\Controllers\Api\Guest;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ---------------------------------------------------------------------------------------------------------------//
    //                                                     ADMIN                                                      //
    // ---------------------------------------------------------------------------------------------------------------//
    Route::prefix('admin')->group(function () {
        // ----------------------------------------------------------------//
        //                          AUTH METHODS                           //
        // ----------------------------------------------------------------//
        Route::post('/login', [Admin\AuthController::class, 'login']);
        Route::middleware(['auth:sanctum', 'can:access-admin', 'check.status'])->group(function () {
            // ----------------------------------------------------------------//
            //                  ADMIN-NOTIFICATIONS METHODS                    //
            // ----------------------------------------------------------------//
            Route::prefix('notifications')->group(function () {
                Route::post('/send-all', [Admin\NotificationController::class, 'sendToAll']);
            });
            // ----------------------------------------------------------------//
            //                      ADMIN-USERS METHODS                        //
            // ----------------------------------------------------------------//
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
    // ---------------------------------------------------------------------------------------------------------------//
    //                                                     USERS                                                      //
    // ---------------------------------------------------------------------------------------------------------------//
    Route::middleware(['auth:sanctum', 'check.status'])->group(function () {
        // ----------------------------------------------------------------//
        //                        PROFILE METHODS                          //
        // ----------------------------------------------------------------//
        Route::prefix('profile')->group(function () {
            Route::get('/', [User\ProfileController::class, 'me']);
            Route::get('/notifications', [User\ProfileController::class, 'notifications']);
            Route::get('/unread-notifications-count', [User\ProfileController::class, 'unreadCount']);
            Route::patch('/', [User\ProfileController::class, 'update']);
            Route::delete('/', [User\ProfileController::class, 'destroy']);
        });
        // ----------------------------------------------------------------//
        //                        PLAYER METHODS                           //
        // ----------------------------------------------------------------//
        Route::prefix('player')->group(function () {
            Route::prefix('checklist')->group(function () {
                Route::get('/', [User\PlayerController::class, 'show']);
                Route::post('/', [User\PlayerController::class, 'storeOrUpdate']);
                Route::post('/complete', [User\PlayerController::class, 'complete']);
                Route::post('/day-off', [User\PlayerController::class, 'setDayOff']);
            });
            Route::get('/statistics', [User\PlayerController::class, 'statistics']);
        });
        // ----------------------------------------------------------------//
        //                        CAPTAIN METHODS                          //
        // ----------------------------------------------------------------//
        Route::prefix('captain')->middleware(['can:captain-leader'])->group(function () {});
    });
    Route::group([], function () {
        // ----------------------------------------------------------------//
        //                          AUTH METHODS                           //
        // ----------------------------------------------------------------//
        Route::post('/forgot-password', [Guest\AuthController::class, 'sendResetLink']);
        Route::post('/reset-password', [Guest\AuthController::class, 'resetPassword']);
        Route::prefix('auth')->group(function () {
            Route::post('/register', [Guest\AuthController::class, 'register']);
            Route::post('/login', [Guest\AuthController::class, 'login']);
        });
    });
});
