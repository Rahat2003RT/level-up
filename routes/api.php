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
        // ----------------------------------------------------------------//
        //                          ADMIN METHODS                          //
        // ----------------------------------------------------------------//
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
            Route::get('/players', [Admin\UserController::class, 'indexPlayers']);
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
            Route::patch('/goals', [User\ProfileController::class, 'storeGoal']);
            Route::patch('/change-password', [User\ProfileController::class, 'changePassword']);
            Route::delete('/', [User\ProfileController::class, 'destroy']);
        });

        // ----------------------------------------------------------------//
        //                        PLAYER METHODS                           //
        // ----------------------------------------------------------------//
        Route::middleware(['can:access-player'])->prefix('player')->group(function () {
            Route::get('/progress', [User\PlayerController::class, 'progress']);
            // ----------------------------------------------------------------//
            //                       CHECKLIST METHODS                         //
            // ----------------------------------------------------------------//
            Route::prefix('checklist')->group(function () {
                Route::get('/', [User\PlayerController::class, 'showChecklist']);
                Route::post('/', [User\PlayerController::class, 'storeChecklist']);
                Route::post('/day-off', [User\PlayerController::class, 'setDayOff']);
            });
            // ----------------------------------------------------------------//
            //                        CONTACT METHODS                          //
            // ----------------------------------------------------------------//
            Route::prefix('contacts')->group(function () {
                Route::get('/', [User\PlayerController::class, 'contacts']);
                Route::post('/', [User\PlayerController::class, 'storeContact']);
                Route::patch('/{contact}', [User\PlayerController::class, 'updateContact']);
                Route::delete('/{contact}', [User\PlayerController::class, 'destroyContact']);
            });
            // ----------------------------------------------------------------//
            //                       STATISTICS METHODS                        //
            // ----------------------------------------------------------------//
            Route::get('/statistics', [User\PlayerController::class, 'statistics']);
            // ----------------------------------------------------------------//
            //                          TEAMS METHODS                          //
            // ----------------------------------------------------------------//
            Route::prefix('team-invitation')->group(function () {
                Route::get('/{token}', [User\LeaderController::class, 'getTeamByToken']);
                Route::post('/{token}/answer', [User\LeaderController::class, 'answerInvitation']);
            });
        });
        // ----------------------------------------------------------------//
        //                        LEADER METHODS                           //
        // ----------------------------------------------------------------//
        Route::middleware(['can:access-leader'])->prefix('leader')->group(function () {
            Route::post('/invite-link', [User\LeaderController::class, 'generateInviteLink']);
            Route::get('/team-members', [User\LeaderController::class, 'teamMembers']);
            Route::delete('/kick/{player}', [User\LeaderController::class, 'kickPlayer']);
        });
    });
    Route::group([], function () {
        // ----------------------------------------------------------------//
        //                          AUTH METHODS                           //
        // ----------------------------------------------------------------//
        Route::prefix('auth')->group(function () {
            Route::post('/register', [Guest\AuthController::class, 'register']);
            Route::post('/login', [Guest\AuthController::class, 'login']);
        });
        // ----------------------------------------------------------------//
        //                       PASSWORD METHODS                          //
        // ----------------------------------------------------------------//
        Route::prefix('password')->group(function () {
            Route::post('/forgot', [Guest\AuthController::class, 'sendResetCode']);
            Route::post('/verify-code', [Guest\AuthController::class, 'verifyResetCode']);
            Route::post('/reset', [Guest\AuthController::class, 'resetPassword']);
        });
    });
});
