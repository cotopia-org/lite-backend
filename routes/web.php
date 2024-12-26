<?php

use Agence104\LiveKit\RoomServiceClient;
use App\Models\Activity;
use App\Models\Contract;
use App\Models\Job;
use App\Utilities\Constants;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('https://lite.cotopia.social');
});
Route::get('/avatar', function () {
    $numericValue = (int)today()->timestamp;

    // Use a hashing function to generate a more evenly distributed value.
    $hashedValue = crc32($numericValue);

    // Ensure the hashed value is positive.
    $hashedValue = abs($hashedValue);

    // Generate RGB values. We'll use modulo operations to constrain the values to 0-255.
    $red = $hashedValue % 256;
    $green = ($hashedValue * 3) % 256; // Multiply by a prime number for better distribution
    $blue = ($hashedValue * 7) % 256;  // Use a different prime number

    // Convert RGB to hex color code.
    $hexColor = sprintf("#%02x%02x%02x", $red, $green, $blue);

    return $hexColor;
    return generateAvatar("Katerou22");


});

Route::get('/tester', function () {


    return 'okay';


});


Route::get('/isNowInUserSchedule', function () {

    $user_id = request('user_id');
    if ($user_id === NULL) {

        $data = [];
        foreach (\App\Models\User::all() as $user) {
            $data[$user->id] = isNowInUserSchedule($user, 1);
        }
        return ['data' => $data, 'now' => now()];

    }

    $user = \App\Models\User::find($user_id);
    return isNowInUserSchedule($user, 1);


});


Route::get('/actoverhead', function () {

    $users = \App\Models\User::all();

    $data = [];
    foreach ($users as $user) {
        $activities = Activity::where('created_at', '>=', \Carbon\Carbon::make('2024-10-01 00:00:00'))
                              ->orderBy('id', 'ASC')->where('user_id', $user->id)->get();


        $prev = NULL;

        foreach ($activities as $activity) {

            if ($prev !== NULL) {

                if ($activity->join_at->lt($prev->left_at)) {
                    $data[] = [
                        'before' => $prev,
                        'after'  => $activity,
                    ];
                }
            }
            $prev = $activity;


        }
    }

    return $data;


});
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);


//Route::get('/l/{link}', 'LinkController@redirect');
Route::get('/lastMonth', function () {
    $firstOfMonth = today()->subMonth()->firstOfMonth();
    $lastOfMonth = today()->subMonth()->lastOfMonth();

    $workspace = \App\Models\Workspace::first();
    $users = $workspace->users;
    $acts = DB::table('activities')
              ->select('user_id', DB::raw('SUM(TIMESTAMPDIFF(SECOND, join_at, IFNULL(left_at, NOW())) / 60) as sum_minutes'))
              ->where('created_at', '>=', $firstOfMonth)->where('created_at', '<=', $lastOfMonth)->groupBy('user_id')
              ->where('workspace_id', 1)->get();
    $d = [];


    \Carbon\CarbonInterval::setCascadeFactors([
                                                  'minute' => [60, 'seconds'],
                                                  'hour'   => [60, 'minutes'],
                                              ]);
    foreach ($acts as $act) {
        $user = $users->find($act->user_id);
        if ($user === NULL) {
            continue;
        }
        $d[] = [
            'username'    => $user->username,
            'email'       => $user->email,
            'name'        => $user->email,
            'sum_minutes' => (float)$act->sum_minutes,
            'act'         => $act,
            'sum_hours'   => \Carbon\CarbonInterval::minutes($act->sum_minutes)->cascade()->forHumans(),

        ];
    }
    return api(array_values($d));

});

Route::get('/acts', function () {

    $request = request();
    if ($request->user_id === NULL) {
        $users = \App\Models\User::all();
        $d = [];
        foreach ($users as $user) {

            $d[] = collect($user->getTime($request->startAt, $request->endAt, $request->workspace));
        }
        return collect($d)->sortByDesc('sum_minutes')->values()->toArray();
    }
    $user = \App\Models\User::find($request->user_id);
    return $user->getTime($request->startAt, $request->endAt, $request->workspace);


});


Route::get('/scoreboard', function () {

    \Carbon\CarbonInterval::setCascadeFactors([
                                                  'seconds' => [1_000, 'milliseconds'],

                                                  'minute' => [60, 'seconds'],
                                                  'hour'   => [60, 'minutes'],
                                              ]);
    $request = request();
    $today = today();
    $now = now();
    if ($request->startTime) {
        $today = \Carbon\Carbon::make($request->startTime);
    }
    if ($request->endTime) {
        $now = \Carbon\Carbon::make($request->endTime);
    }
    $acts = \App\Models\Act::where('created_at', '>=', $today)->whereIn('type', ['time_started', 'time_ended'])
                           ->orderBy('id', 'ASC')->where('user_id', $request->user_id)->get();

    $minutes = 0;
    $data = [];
    foreach ($acts as $act) {
        $data[] = [
            'id'         => $act->id,
            'type'       => $act->type,
            'created_at' => $act->created_at
        ];
        if ($act->type === 'time_started') {
            $end = $acts->where('id', '>', $act->id)->where('type', 'time_ended')->first();
            if ($end === NULL) {
                $minutes += $act->created_at->diffInMinutes($now);
            } else {
                $minutes += $act->created_at->diffInMinutes($end->created_at);

            }
        }
    }
    return [
        'minutes' => \Carbon\CarbonInterval::minutes($minutes)->cascade()->forHumans(),
        'data'    => $data,
    ];

});
Route::get('/health', function () {
    return api([
                   'status' => 'ok',
                   'now'    => now()
               ]);
});
