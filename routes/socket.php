<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\SocketController;
use Illuminate\Support\Facades\Route;


Route::prefix('socket')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(SocketController::class)->group(function () {
            Route::post('/updateCoordinates', 'updateCoordinates')->middleware('checkSocketId');
            Route::post('/connected', 'connected');
            Route::get('/disconnected', 'disconnected');


        });


        Route::controller(MessageController::class)->middleware('checkSocketId')->prefix('messages')
             ->group(function () {

                 Route::post('/', 'send');
                 Route::put('/{message:nonce_id}', 'update');
                 Route::delete('/{message:nonce_id}', 'delete');

                 Route::get('/{message:nonce_id}/seen', 'seen');
                 Route::get('/{message:nonce_id}/pin', 'pin');
                 Route::get('/{message:nonce_id}/unPin', 'unPin');


             });

    });

    Route::controller(SocketController::class)->group(function () {
        Route::any('/events', 'events');
    });

});
