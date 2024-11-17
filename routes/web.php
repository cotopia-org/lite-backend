<?php

use Agence104\LiveKit\RoomServiceClient;
use App\Models\Activity;
use App\Models\Job;
use App\Utilities\Constants;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('https://lite.cotopia.social');
});
Route::get('/tester', function () {


    $jobs = \App\Models\Job::all();

    foreach ($jobs as $job) {


        $status = NULL;
        if ($job->status === Constants::IN_PROGRESS) {
            $status = 'In Progress 🔵';
        }
        if ($job->status === Constants::PAUSED) {
            $status = 'Paused 🟡';
        }
        if ($job->status === Constants::COMPLETED) {
            $status = 'Completed 🟢';
        }
        $estimate = 1;
        if ($job->estimate !== NULL) {
            $estimate = $job->estimate;
        }

        $user = $job->users->first();
        $text = "Job #$job->id by: @$user->username

**$job->title**
$job->description

$status

$estimate hrs ⏰
";

        $msg = sendMessage($text, 39);

        Job::withoutEvents(function () use ($job, $msg) {
            $job->update([
                             'message_id' => $msg->id
                         ]);
        });
    }
    dd('Okay');
    //    sleep(60);
    //    dd($job->end($user, 'paused'));
    //    $user = \App\Models\User::find(3);
    //    $user->notify(new \App\Notifications\newNotification('Salam Test'));
    //    \App\Models\User::find(3)->notify('Salam Test');
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
              ->select('user_id',
                       DB::raw('SUM(TIMESTAMPDIFF(SECOND, join_at, IFNULL(left_at, NOW())) / 60) as sum_minutes'))
              ->where('created_at', '>=', $firstOfMonth)->where('created_at', '<=', $lastOfMonth)->groupBy('user_id')
              ->get();
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
            'sum_minutes' => (float) $act->sum_minutes,
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
