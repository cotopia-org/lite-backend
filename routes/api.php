<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PostmanExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TalkController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');


Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register');
    Route::post('/checkUsername', 'checkUsername');
});

Route::controller(InviteController::class)->prefix('invites')->group(function () {
    Route::post('/', 'invite')->middleware('auth:sanctum');
    Route::get('/{code}/join', 'join')->middleware('auth:sanctum');
    Route::get('/{code}/decline', 'decline')->middleware('auth:sanctum');

    Route::get('/{code}', 'get');

});

Route::middleware('auth:sanctum')->group(callback: function () {


    Route::controller(ScheduleController::class)->prefix('schedules')->group(function () {
        Route::post('/', 'create');
        Route::put('/{schedule}', 'update');
        Route::delete('/{schedule}', 'delete');

    })->middleware('checkIsUserOnline');

    Route::controller(UserController::class)->prefix('users')->group(function () {
        Route::get('/me', 'me');
        Route::get('/activities', 'activities')->middleware('checkIsUserOnline');
        Route::get('/chats', 'chats')->middleware('checkIsUserOnline');
        Route::get('/{user}/jobs', 'jobs')->middleware('checkIsUserOnline');
        Route::get('/{user}/schedules', 'schedules')->middleware('checkIsUserOnline');
        Route::get('/{user}/scheduleFulfillment', 'scheduleFulfillment')->middleware('checkIsUserOnline');
        Route::get('/talks', 'talks')->middleware('checkIsUserOnline');
        Route::get('/unGhost', 'unGhost')->middleware('checkIsUserOnline');
        Route::get('/', 'all')->middleware('checkIsUserOnline');
        Route::post('/', 'update')->middleware('checkIsUserOnline');
        Route::post('/updateCoordinates', 'updateCoordinates')->middleware('checkIsUserOnline');
        Route::get('/toggleMegaphone', 'toggleMegaphone')->middleware('checkIsUserOnline');
        Route::post('/search', 'search')->middleware('checkIsUserOnline');
        Route::put('/update', 'update')->middleware('checkIsUserOnline');
        Route::get('/settings', 'settings');
    });

    Route::controller(WorkspaceController::class)->prefix('workspaces')->group(function () {
        Route::get('/', 'all');
        Route::post('/', 'create');
        //        Route::get('/{workspace}', 'get')->middleware('ownedWorkspace');
        Route::get('/{workspace}', 'get')->middleware('checkIsUserOnline');
        Route::get('/{workspace}/join', 'join');
        Route::get('/{workspace}/rooms', 'rooms')->middleware('checkIsUserOnline');
        Route::get('/{workspace}/jobs', 'jobs')->middleware('checkIsUserOnline');
        Route::get('/{workspace}/users', 'users')->middleware('checkIsUserOnline');
        Route::get('/{workspace}/leaderboard', 'leaderboard')->middleware('checkIsUserOnline');
        Route::get('/{workspace}/schedules', 'schedules')->middleware('checkIsUserOnline');
        Route::get('/{workspace}/tags', 'tags')->middleware('checkIsUserOnline');
        Route::post('/{workspace}/addRole', 'addRole')->middleware('checkIsUserOnline');
        Route::post('/{workspace}/addTag', 'addTag')->middleware('checkIsUserOnline');
        Route::put('/{workspace}', 'update')->middleware('checkIsUserOnline');

    });

    Route::controller(RoomController::class)->prefix('rooms')->group(function () {
        Route::post('/', 'create')->middleware('checkIsUserOnline');
        Route::get('/leave', 'leave')->middleware('checkIsUserOnline');
        Route::get('/{room}/', 'get');
        Route::put('/{room}/', 'update')->middleware('checkIsUserOnline');
        Route::get('/{room}/join', 'join');
        Route::delete('/{room}', 'delete')->middleware('checkIsUserOnline');

    });

    Route::controller(FileController::class)->prefix('files')->group(function () {
        Route::get('/', 'all');
        Route::post('/', 'upload');
        Route::delete('/{file}', 'delete');
    });

    Route::controller(MessageController::class)->prefix('messages')->group(function () {
        Route::get('/searchMention', 'searchMention');
        Route::get('/{room}', 'get');
        Route::get('/{message}/seen', 'seen');
        Route::get('/{message}/pin', 'pin');
        Route::get('/{message}/unPin', 'unPin');
        Route::post('/', 'send');
        Route::put('/{message}', 'update');
        Route::delete('/{message}', 'delete');
    })->middleware('checkIsUserOnline');


    Route::controller(JobController::class)->prefix('jobs')->group(function () {
        Route::post('/', 'create');
        Route::get('/{job}', 'get');
        Route::put('/{job}', 'update');
        Route::delete('/{job}', 'delete');

    })->middleware('checkIsUserOnline');


    Route::controller(TalkController::class)->prefix('talks')->group(function () {
        Route::post('/', 'talk');
        Route::post('/{talk}', 'respond');

    })->middleware('checkIsUserOnline');


    Route::controller(ReportController::class)->prefix('reports')->group(function () {
        Route::get('/', 'all');
        Route::post('/', 'create');

    });

    Route::controller(SettingController::class)->prefix('settings')->group(function () {
        Route::post('/', 'set');

    })->middleware('checkIsUserOnline');


    Route::controller(ChatController::class)->prefix('chats')->group(function () {
        Route::post('/createDirect', 'createDirect');
        Route::post('/createGroup', 'createGroup');
        Route::post('/createChannel', 'createChannel');
        Route::delete('/{chat}/', 'delete');
        Route::get('/{chat}/messages', 'messages');
        Route::get('/{chat}/participants', 'participants');
        Route::get('/{chat}/pinnedMessages', 'pinnedMessages');

    })->middleware('checkIsUserOnline');


    Route::controller(ContractController::class)->prefix('contracts')->group(function () {
        Route::get('/', 'all');
        Route::post('/', 'create');
        Route::get('/{contract}', 'get');
        Route::put('/{contract}', 'update');
        Route::get('/{contract}/payments', 'payments');

    });


    Route::controller(PaymentController::class)->prefix('payments')->group(function () {
        Route::get('/', 'all');
        Route::post('/', 'create');
        Route::get('/{payment}', 'get');
        Route::put('/{payment}', 'update');

    });


});

Route::any('/github/webhook', 'App\Http\Controllers\GithubController@webhook');
Route::get('export-postman', PostmanExportController::class)->name('postman');

require __DIR__ . '/socket.php';
