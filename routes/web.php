<?php

use Agence104\LiveKit\RoomServiceClient;
use App\Models\Activity;
use App\Models\Contract;
use App\Models\Job;
use App\Utilities\Constants;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('https://lite.cotopia.social');
});
Route::get('/tester', function () {


    $userJobs = \Illuminate\Support\Facades\DB::table('job_user')->whereNot('status', Constants::IN_PROGRESS)->get();

    foreach ($userJobs as $userJob) {
        $end = \App\Models\Act::where('job_id', $userJob->job_id)->whereType('job_ended')->first();
        if ($end === NULL) {
            $start = \App\Models\Act::where('job_id', $userJob->job_id)->whereType('job_started')->first();
            if ($start !== NULL) {
                $job = Job::find($userJob->job_id);
                \App\Models\Act::create([
                                            'user_id'      => $userJob->user_id,
                                            'workspace_id' => 1,
                                            'room_id'      => 105,
                                            'job_id'       => $userJob->job_id,
                                            'type'         => 'job_ended',
                                            'description'  => 'CUSTOM BY ADMIN',
                                            'created_at'   => $userJob->created_at === NULL ? $job->updated_at->addHours(Job::find($userJob->job_id)->estimate) : $userJob->created_at->addHours(Job::find($userJob->job_id)->estimate),
                                        ]);


            }
        }


    }

    return 'okay';


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
