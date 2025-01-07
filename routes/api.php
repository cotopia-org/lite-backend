<?php

use App\Http\Controllers\ActivityController;
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
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TagController;
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
        Route::get('/activities', 'activities');
        Route::get('/chats', 'chats');
        Route::get('/mentionedJobs', 'mentionedJobs');
        Route::get('/{user}/jobs', 'jobs');
        Route::get('/{user}/tags', 'tags');
        Route::get('/{user}/payments', 'payments');
        Route::get('/{user}/contracts', 'contracts');
        Route::get('/{user}/schedules', 'schedules');
        Route::get('/{user}/scheduleFulfillment', 'scheduleFulfillment');
        Route::get('/talks', 'talks');
        Route::get('/unGhost', 'unGhost');
        Route::get('/', 'all');
        Route::post('/', 'update');
        Route::post('/updateCoordinates', 'updateCoordinates');
        Route::get('/toggleMegaphone', 'toggleMegaphone');
        Route::post('/search', 'search');
        Route::put('/update', 'update');
        Route::get('/settings', 'settings');
        Route::get('/beAfk', 'beAfk');
        Route::get('/beOnline', 'beOnline');
    });

    Route::controller(WorkspaceController::class)->prefix('workspaces')->group(function () {
        Route::get('/', 'all');
        Route::post('/', 'create');
        //        Route::get('/{workspace}', 'get')->middleware('ownedWorkspace');
        Route::get('/{workspace}', 'get');
        Route::get('/{workspace}/join', 'join');
        Route::get('/{workspace}/rooms', 'rooms');
        Route::get('/{workspace}/jobs', 'jobs');
        Route::get('/{workspace}/users', 'users');
        Route::get('/{workspace}/leaderboard', 'leaderboard');
        Route::get('/{workspace}/schedules', 'schedules');
        Route::get('/{workspace}/tags', 'tags');
        Route::post('/{workspace}/addRole', 'addRole');
        Route::put('/{workspace}', 'update');

    });

    Route::controller(RoomController::class)->prefix('rooms')->group(function () {
        Route::post('/', 'create');
        Route::get('/leave', 'leave');
        Route::get('/{room}/', 'get');
        Route::put('/{room}/', 'update');
        Route::get('/{room}/join', 'join');
        Route::get('/{room}/switch', 'switch');
        Route::delete('/{room}', 'delete');

    });

    Route::controller(FileController::class)->prefix('files')->group(function () {
        Route::get('/', 'all');
        Route::post('/', 'upload');
        Route::delete('/{file}', 'delete');
    });

    Route::controller(MessageController::class)->prefix('messages')->group(function () {
        Route::get('/searchMention', 'searchMention');
        Route::get('/{message:nonce_id}', 'get');
        Route::get('/{message}/seen', 'seen');
        Route::get('/{message}/pin', 'pin');
        Route::get('/{message}/unPin', 'unPin');
        Route::post('/', 'send');
        Route::put('/{message}', 'update');
        Route::delete('/{message}', 'delete');


        Route::post('/{message}/react', 'react');
        Route::get('/{message}/translate', 'translate');


    })->middleware('checkIsUserOnline');

    Route::controller(JobController::class)->prefix('jobs')->group(function () {
        Route::post('/', 'create');
        Route::get('/{job}', 'get');
        Route::put('/{job}', 'update');

        Route::get('/{job}/updateStatus', 'updateStatus');
        Route::get('/{job}/accept', 'accept');
        Route::get('/{job}/dismiss', 'dismiss');
        Route::get('/{job}/jobs', 'jobs');


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
        Route::get('/{chat}/', 'get');

        Route::post('/createDirect', 'createDirect');
        Route::post('/createGroup', 'createGroup');
        Route::post('/createChannel', 'createChannel');
        Route::delete('/{chat}/', 'delete');
        Route::get('/{chat}/messages', 'messages');
        Route::get('/{chat}/sawMessages', 'sawMessages');
        Route::get('/{chat}/unreadMessages', 'unreadMessages');
        Route::get('/{chat}/participants', 'participants');
        Route::get('/{chat}/pinnedMessages', 'pinnedMessages');
        Route::get('/{chat}/getLastUnSeenMessagePage', 'getLastUnSeenMessagePage');
        Route::get('/{chat}/{message}/getMessagePage', 'getMessagePage');

        Route::get('/{chat}/toggleMute', 'toggleMute');


    })->middleware('checkIsUserOnline');

    Route::controller(ContractController::class)->prefix('contracts')->group(function () {
        Route::get('/', 'all');
        Route::post('/', 'create');
        Route::get('/getAllContents', 'getAllContents');

        Route::delete('/{contract}', 'delete');
        Route::get('/{contract}', 'get');
        Route::put('/{contract}', 'update');
        Route::get('/{contract}/payments', 'payments');
        Route::get('/{contract}/adminSign', 'adminSign');
        Route::get('/{contract}/userSign', 'userSign');


        Route::get('/{contract}/adminRevoke', 'adminRevoke');
        Route::get('/{contract}/userRevoke', 'userRevoke');


    });

    Route::controller(PaymentController::class)->prefix('payments')->group(function () {
        Route::get('/', 'all');
        Route::post('/', 'create');
        Route::get('/{payment}', 'get');
        Route::put('/{payment}', 'update');

    });

    Route::controller(SearchController::class)->prefix('search')->group(function () {
        Route::get('/', 'search');


    });

    Route::controller(TagController::class)->prefix('tags')->group(function () {
        Route::post('/', 'create');
        Route::get('/{tag}', 'get');
        Route::put('/{tag}', 'update');
        Route::post('/{tag}/addMember', 'addMember');
        Route::post('/{tag}/removeMember', 'removeMember');
        Route::delete('/{tag}', 'delete');


        Route::get('/{tag}/jobs', 'jobs');


    });


    Route::controller(ActivityController::class)->prefix('activities')->group(function () {
        Route::get('/{user}', 'get');


    });
});

Route::any('/github/webhook', 'App\Http\Controllers\GithubController@webhook');
Route::get('export-postman', PostmanExportController::class)->name('postman');

require __DIR__ . '/socket.php';
