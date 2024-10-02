<?php

use App\Models\Activity;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('https://lite.cotopia.social');
});


Route::get('/tester', function () {

    $chat = \App\Models\Chat::first();
    dd($chat->workspace->users);

    $users = \App\Models\User::all();
    $acts = DB::table('activities')
              ->select('user_id', DB::raw('SUM(TIMESTAMPDIFF(MINUTE, join_at, IFNULL(left_at, NOW()))) as sum_minutes'))
              ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->groupBy('user_id')->get();
    $d = [];
    foreach ($acts as $act) {
        $d[] = [
            'sum_minutes' => $act->sum_minutes,
            'user'        => $users->find($act->user_id),
        ];
    }
    return $d;
    return $acts;

});
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);


//Route::get('/l/{link}', 'LinkController@redirect');


Route::get('/acts', function () {
    $request = request();
    if ($request->user_id === NULL) {
        $users = \App\Models\User::all();
        $d = [];
        foreach ($users as $user) {

            $d[] = collect($user->getTime($request->period, $request->startAt, $request->endAt, $request->expanded,
                                          $request->workspace));
        }
        return collect($d)->sortByDesc('sum_minutes')->values()->toArray();
    }
    $user = \App\Models\User::find($request->user_id);
    return $user->getTime($request->period, $request->startAt, $request->endAt, $request->expanded,
                          $request->workspace);


});


Route::get('/scoreboard', function () {

    $request = request();

    $users = \App\Models\User::all();
    $d = [];
    foreach ($users as $user) {

        $d[] = collect($user->getTime($request->period, $request->startAt, $request->endAt, $request->expanded,
                                      $request->workspace));
    }
    return collect($d)->sortByDesc('sum_minutes')->pluck('sum_hours', 'user.username')->all();


});
Route::get('/health', function () {
    return api([
                   'status' => 'ok',
                   'now'    => now()
               ]);
});
