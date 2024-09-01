<?php

use App\Http\Controllers\SocketController;
use Illuminate\Support\Facades\Route;


Route::prefix('socket')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(SocketController::class)->group(function () {
            Route::post('/updateCoordinates', 'updateCoordinates');
            Route::post('/connected', 'connected');
            Route::get('/disconnected', 'disconnected');
            Route::get('messages/{message}/seen', 'seen');


        });
    });

    Route::controller(SocketController::class)->group(function () {
        Route::any('/events', 'events');
    });

});
