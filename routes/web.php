<?php

use Agence104\LiveKit\RoomServiceClient;
use App\Models\Activity;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('https://lite.cotopia.social');
});


Route::get('/tester', function () {

    $users = \App\Models\User::all();

    $data = [];
    foreach ($users as $user) {
        $activities = Activity::where('created_at', '>=', '2024-10-01 00:00:00')->orderBy('id', 'ASC')
                              ->where('user_id', $user->id)->get();


        $left_at = NULL;

        foreach ($activities as $activity) {
            if ($left_at === NULL) {
                $left_at = $activity->left_at;

                continue;
            }
            if ($activity->left_at <= $left_at) {
                $data[] = $activity;
            }


        }
    }

    return $data;


});
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);


//Route::get('/l/{link}', 'LinkController@redirect');


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
Route::get('/health', function () {
    return api([
                   'status' => 'ok',
                   'now'    => now()
               ]);
});
