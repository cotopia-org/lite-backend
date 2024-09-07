<?php

use Agence104\LiveKit\RoomServiceClient;
use App\Utilities\Constants;
use Illuminate\Support\Facades\Route;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

Route::get('/', function () {
    return redirect('https://lite.cotopia.social');
});


Route::get('/tester', function () {

    $user = \App\Models\User::first();
    return \App\Http\Resources\UserMinimalResource::make($user);

});
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);


Route::get('/l/{link}', 'LinkController@redirect');


Route::get('/acts', function () {
    $request = request();
    if ($request->user_id === NULL) {
        $users = \App\Models\User::all();
        $d = [];
        foreach ($users as $user) {

            $d[] = collect($user->getTime($request->period, $request->startAt, $request->endAt, $request->expanded, $request->workspace));
        }
        return collect($d)->sortByDesc('sum_minutes')->values()->toArray();
    }
    $user = \App\Models\User::find($request->user_id);
    return $user->getTime($request->period, $request->startAt, $request->endAt, $request->expanded, $request->workspace);


});


Route::get('/scoreboard', function () {

    $request = request();

    $users = \App\Models\User::all();
    $d = [];
    foreach ($users as $user) {

        $d[] = collect($user->getTime($request->period, $request->startAt, $request->endAt, $request->expanded, $request->workspace));
    }
    return collect($d)->sortByDesc('sum_minutes')->pluck('sum_hours', 'user.username')->all();


});
