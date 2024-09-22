<?php

use App\Models\Activity;
use App\Notifications\newNotification;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('https://lite.cotopia.social');
});


Route::get('/tester', function () {


//    $socket_users = collect(Http::get('https://ls.cotopia.social/sockets')->json());

    $online_users = \App\Models\User::whereStatus('online')->get();
    dd($online_users);

//    $users = \App\Models\User::find([1, 2]);
//
//
//    dd(Notification::send($users, new NewNotification('Tester')));
////
////
////    dd($user->notify(new NewNotification('Tester')));
//    $notifUser = \App\Models\User::create([
//                                              'name'     => 'Lite Notifications',
//                                              'username' => 'lite_notifications',
//                                              'email'    => 'notifications@cotopia.social',
//                                              'password' => Hash::make('123123'),
//                                              'active'   => TRUE,
//                                              'status'   => 'online',
//                                              'bio'      => 'Im handling the notifications! :)',
//                                              'is_bot'   => TRUE,
//                                              'verified' => TRUE,
//                                          ]);


//    return $notifUser;

});
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);


Route::get('/l/{link}', 'LinkController@redirect');


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
