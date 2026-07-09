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
        Route::post('/login', [Admin\AuthController::class, 'login'])->name('admin.login');
        // ----------------------------------------------------------------//
        //                          ADMIN METHODS                          //
        // ----------------------------------------------------------------//
        Route::middleware(['auth:sanctum', 'can:access-admin', 'check.status'])->group(function () {
            // ----------------------------------------------------------------//
            //                  ADMIN-NOTIFICATIONS METHODS                    //
            // ----------------------------------------------------------------//
            Route::prefix('notifications')->group(function () {
                Route::post('/send-all',                [Admin\NotificationController::class, 'sendToAll']);
            });
            // ----------------------------------------------------------------//
            //                      ADMIN-USERS METHODS                        //
            // ----------------------------------------------------------------//
            Route::prefix('users')->group(function () {
                Route::get('/',                         [Admin\UserController::class, 'index']);
                Route::get('/{user}',                   [Admin\UserController::class, 'show']);
                Route::patch('/{user}',                 [Admin\UserController::class, 'update']);
                Route::patch('/{user}/role',            [Admin\UserController::class, 'changeRole']);
                Route::post('/{user}/block',            [Admin\UserController::class, 'block']);
                Route::post('/{user}/unblock',          [Admin\UserController::class, 'unblock']);
                Route::post('/{user}/restore',          [Admin\UserController::class, 'restore'])->withTrashed();
                Route::delete('/{user}',                [Admin\UserController::class, 'destroy']);
                Route::delete('/{user}/force-delete',   [Admin\UserController::class, 'forceDelete'])->withTrashed();
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
            Route::get('/',                 [User\ProfileController::class, 'me']);
            Route::patch('/',               [User\ProfileController::class, 'update']);
            Route::delete('/',              [User\ProfileController::class, 'destroy']);
            Route::patch('/goal',           [User\ProfileController::class, 'storeGoal']);
            Route::patch('/password',       [User\ProfileController::class, 'changePassword']);
        });
        // ----------------------------------------------------------------//
        //                     NOTIFICATIONS METHODS                       //
        // ----------------------------------------------------------------//
        Route::prefix('notifications')->group(function () {
            Route::get('/',                 [User\NotificationController::class, 'notifications']);
            Route::get('/unread-count',     [User\NotificationController::class, 'unreadCount']);
        });
        // ----------------------------------------------------------------//
        //                        PLAYER METHODS                           //
        // ----------------------------------------------------------------//
        Route::middleware(['can:access-player'])->prefix('player')->group(function () {
            // ----------------------------------------------------------------//
            //                        PROGRESS METHODS                         //
            // ----------------------------------------------------------------//
            Route::prefix('progress')->group(function () {
                Route::get('/',                             [User\PlayerController::class, 'progress']);
            });
            // ----------------------------------------------------------------//
            //                       CHECKLIST METHODS                         //
            // ----------------------------------------------------------------//
            Route::prefix('checklist')->group(function () {
                Route::get('/',                             [User\PlayerController::class, 'showChecklist']);
                Route::post('/',                            [User\PlayerController::class, 'storeChecklist']);
                Route::post('/day-off',                     [User\PlayerController::class, 'setDayOff']);
            });
            // ----------------------------------------------------------------//
            //                        CONTACT METHODS                          //
            // ----------------------------------------------------------------//
            Route::prefix('contacts')->group(function () {
                Route::get('/',                             [User\PlayerController::class, 'contacts']);
                Route::post('/',                            [User\PlayerController::class, 'storeContact']);
                Route::patch('/{contact}',                  [User\PlayerController::class, 'updateContact']);
                Route::delete('/{contact}',                 [User\PlayerController::class, 'destroyContact']);
            });
            // ----------------------------------------------------------------//
            //                       STATISTICS METHODS                        //
            // ----------------------------------------------------------------//
            Route::prefix('statistics')->group(function () {
                Route::get('/',                             [User\PlayerController::class, 'statistics']);
            });
        });
        // ----------------------------------------------------------------//
        //                        LEADER METHODS                           //
        // ----------------------------------------------------------------//
        Route::middleware(['can:access-leader'])->prefix('leader')->group(function () {
            // ----------------------------------------------------------------//
            //                       STATISTICS METHODS                        //
            // ----------------------------------------------------------------//
            Route::prefix('statistics')->group(function () {
                Route::get('/', [User\LeaderController::class, 'dashboardStatistics']);
            });
            // ----------------------------------------------------------------//
            //                        CONTACT METHODS                          //
            // ----------------------------------------------------------------//
            Route::prefix('contacts')->group(function () {
                Route::get('/', [User\LeaderController::class, 'contacts']);
                Route::post('/', [User\LeaderController::class, 'storeContact']);
                Route::patch('/{contact}', [User\LeaderController::class, 'updateContact']);
                Route::delete('/{contact}', [User\LeaderController::class, 'destroyContact']);
            });
            // ----------------------------------------------------------------//
            //                       CHECKLIST METHODS                         //
            // ----------------------------------------------------------------//
            Route::prefix('checklist')->group(function () {
                Route::get('/', [User\LeaderController::class, 'showChecklist']);
                Route::post('/', [User\LeaderController::class, 'storeChecklist']);
                Route::post('/day-off', [User\LeaderController::class, 'setDayOff']);
            });
        });
        // ----------------------------------------------------------------//
        //                          TEAMS METHODS                          //
        // ----------------------------------------------------------------//
        Route::prefix('team')->group(function () {
            // ----------------------------------------------------------------//
            //                         MEMBERS METHODS                         //
            // ----------------------------------------------------------------//
            Route::prefix('members')->group(function () {
                Route::get('/',                 [User\TeamController::class, 'getMembers']);
                Route::delete('/{member}',      [User\TeamController::class, 'kickMember']);
            });
            // ----------------------------------------------------------------//
            //                           PLAN METHODS                          //
            // ----------------------------------------------------------------//
            Route::prefix('plan')->group(function () {
                Route::get('/',                 [User\TeamController::class, 'getTeamPlan']);
                Route::patch('/',               [User\TeamController::class, 'updateTeamPlan']);
            });
            // ----------------------------------------------------------------//
            //                        INVITATIONS METHODS                      //
            // ----------------------------------------------------------------//
            Route::prefix('invitations')->group(function () {
                Route::post('/',                [User\TeamController::class, 'generateInviteLink']);
                Route::post('/{token}/respond', [User\TeamController::class, 'answerInvitation']);
                Route::get('/{token}',          [User\TeamController::class, 'getTeamByToken']);
            });
            // ----------------------------------------------------------------//
            //                         ACTIONS METHODS                         //
            // ----------------------------------------------------------------//
            Route::prefix('actions')->group(function () {
                Route::post('/leave',           [User\TeamController::class, 'leaveTeam']);
            });
        });
    });
    // ---------------------------------------------------------------------------------------------------------------//
    //                                                     GUEST                                                      //
    // ---------------------------------------------------------------------------------------------------------------//
    Route::group([], function () {
        // ----------------------------------------------------------------//
        //                          AUTH METHODS                           //
        // ----------------------------------------------------------------//
        Route::prefix('auth')->group(function () {
            Route::post('/register', [Guest\AuthController::class, 'register']);
            Route::post('/login', [Guest\AuthController::class, 'login'])->name('login');
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
