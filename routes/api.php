<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\User;
use App\Http\Controllers\Api\Guest;
use App\Http\Controllers\Api\User\PlanController;
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
            //                       ADMIN-TARIFFS METHODS                     //
            // ----------------------------------------------------------------//
            Route::prefix('tariffs')->group(function () {
                Route::get('/',         [Admin\TariffController::class, 'index']);
                Route::post('/',        [Admin\TariffController::class, 'store']);
                Route::put('/{tariff}', [Admin\TariffController::class, 'update']);
                Route::delete('/{tariff}', [Admin\TariffController::class, 'destroy']);
            });
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
        //                        CONTACT METHODS                          //
        // ----------------------------------------------------------------//
        Route::apiResource('contacts', User\ContactController::class)->except(['show']);
        // ----------------------------------------------------------------//
        //                        PROGRESS METHODS                         //
        // ----------------------------------------------------------------//
        Route::prefix('plan')->group(function () {
            // ----------------------------------------------------------------//
            //                        PROGRESS METHODS                         //
            // ----------------------------------------------------------------//
            Route::prefix('progress')->group(function () {
                Route::get('/', [PlanController::class, 'progress']);
            });
            // ----------------------------------------------------------------//
            //                       CHECKLIST METHODS                         //
            // ----------------------------------------------------------------//
            Route::prefix('checklist')->group(function () {
                Route::get('/',         [PlanController::class, 'checklist']);
                Route::post('/',        [PlanController::class, 'storeChecklist']);
                Route::post('/day-off', [PlanController::class, 'setDayOff']);
            });
            Route::prefix('statistics')->group(function () {
                Route::get('/', [PlanController::class, 'statistics']);
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
                Route::get('/team', [User\LeaderController::class, 'teamStatistics']);
            });
        });
        // ----------------------------------------------------------------//
        //                          CHATS METHODS                          //
        // ----------------------------------------------------------------//
        Route::prefix('chats')->group(function () {
           Route::get('/', [User\ChatController::class, 'index']);
            // ----------------------------------------------------------------//
            //                         MESSAGE METHODS                         //
            // ----------------------------------------------------------------//
            Route::prefix('{chat}')->group(function () {
                Route::get('/messages',          [User\MessageController::class, 'index']);
                Route::post('/messages',         [User\MessageController::class, 'store']);
                Route::post('/messages/read',       [User\MessageController::class, 'read']);
                Route::patch('/messages/{message}', [User\MessageController::class, 'update']);
                // ----------------------------------------------------------------//
                //                        PRESENCE METHODS                         //
                // ----------------------------------------------------------------//
                Route::post('/ping',                [User\ChatPresenceController::class, 'ping']);
                Route::post('/leave',               [User\ChatPresenceController::class, 'leave']);
            });
        });
        // ----------------------------------------------------------------//
        //                          TEAMS METHODS                          //
        // ----------------------------------------------------------------//
        Route::prefix('team')->group(function () {
            // ----------------------------------------------------------------//
            //                        INVITATIONS METHODS                      //
            // ----------------------------------------------------------------//
            Route::prefix('invitations')->group(function () {
                Route::post('/',                [User\TeamController::class, 'generateInviteLink']);
                Route::post('/{token}/respond', [User\TeamController::class, 'answerInvitation']);
                Route::get('/{token}',          [User\TeamController::class, 'getTeamByToken']);
            });
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
