<?php

use Agence104\LiveKit\RoomServiceClient;
use App\Utilities\Constants;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('https://lite.cotopia.social');
});


Route::get('/tester', function () {


    \App\Jobs\disconnectLivekitJob::dispatch(\App\Models\Room::first(), \App\Models\User::first());
    dd('Here');
    return \App\Http\Resources\UserResource::make(\App\Models\User::first());
    dd($room->isUserInLk(\App\Models\User::find(3)));
    $host = config('livekit.host');
    dd($host);
    $svc = new RoomServiceClient($host, config('livekit.apiKey'), config('livekit.apiSecret'));
    dd('Here');
    $user = \App\Models\User::first();

    //
    //    $workspace = \App\Models\Workspace::first();
    //    $user->giveRole('member', $workspace);


    $token = $user->createToken('1');
    dd($token);
    $room = \App\Models\Room::find(2);
    dd($room->lkUsers());
    //
    //    $host = 'https://live-kit-server.cotopia.social';
    //    $svc = new RoomServiceClient($host, config('livekit.apiKey'), config('livekit.apiSecret'));
    //    return $svc->removeParticipant('1', 'Katerou22');
    //
    //    $e = array(
    //        'event' => 'participant_left',
    //        'room' =>
    //            array(
    //                'sid' => 'RM_k5kESqHwxEyt',
    //                'name' => '1',
    //                'emptyTimeout' => 300,
    //                'departureTimeout' => 20,
    //                'creationTime' => '1721849273',
    //                'turnPassword' => 'vpJFoEFWkG01UQfOxuy12gp56IXmFNcsr7xL2EPf0xn',
    //                'enabledCodecs' =>
    //                    array(
    //                        0 =>
    //                            array(
    //                                'mime' => 'audio/opus',
    //                            ),
    //                        1 =>
    //                            array(
    //                                'mime' => 'audio/red',
    //                            ),
    //                        2 =>
    //                            array(
    //                                'mime' => 'video/VP8',
    //                            ),
    //                        3 =>
    //                            array(
    //                                'mime' => 'video/H264',
    //                            ),
    //                        4 =>
    //                            array(
    //                                'mime' => 'video/VP9',
    //                            ),
    //                        5 =>
    //                            array(
    //                                'mime' => 'video/AV1',
    //                            ),
    //                    ),
    //            ),
    //        'participant' =>
    //            array(
    //                'sid' => 'PA_WsJqW4catifU',
    //                'identity' => 'Katerou22',
    //                'state' => 'DISCONNECTED',
    //                'joinedAt' => '1721850935',
    //                'version' => 4,
    //                'permission' =>
    //                    array(
    //                        'canSubscribe' => true,
    //                        'canPublish' => true,
    //                        'canPublishData' => true,
    //                    ),
    //                'isPublisher' => true,
    //            ),
    //        'id' => 'EV_AceC9wcszMJW',
    //        'createdAt' => '1721851109',
    //    );
    //    dd((new \App\Utilities\EventType($e))->participant()->state);
    //
    //    $host = 'https://live-kit-server.cotopia.social';
    //    $svc = new RoomServiceClient($host, config('livekit.apiKey'), config('livekit.apiSecret'));
    //
    //// List rooms.
    //    $rooms = $svc->listParticipants('1');


    //    $users = \App\Models\User::all();
    //
    //    foreach ($users as $user) {
    //        $last_activity = $user->activities()->orderBy('id', 'desc')->first();
    //        if ($last_activity->event_type !== Constants::LEFT) {
    //            $user->checkIsInRoomForReal();
    //        }
    //    }
    //
    //    $rooms = \App\Models\Room::whereNot('workspace_id', null)->get();
    //    foreach ($rooms as $room) {
    //        foreach ($room->lkUsers() as $lkUser) {
    //            $user = $room->users->where('username', $lkUser->getIdentity())->first();
    //            if ($user === null) {
    //                $u = \App\Models\User::where('username', $lkUser->getIdentity())->first();
    //                $u->activities()->create([
    //                    'event_id' => $event->id,
    //                    'state' => $state,
    //                    'event_type' => $event->event,
    //                    'workspace_id' => $event->room()->workspace->id,
    //                    'room_id' => $event->room()->id,
    //                    'data' => 'Disconn',
    //                ]);
    //            }
    //        }
    //    }


    dd(now()->timezone('Asia/Tehran'));

    $user = \App\Models\User::find(3);
    $acts = $user->activities();

    if (TRUE) {

        //        $acts = $acts->where('created_at', '>=', today());


    }
    $acts = $acts->whereIn('event_type', [
        Constants::JOINED,
        Constants::LEFT
    ])->where('created_at', '>=', today()->subDay())->where('created_at', '<=', today());
    $sum = 0;
    $acts = $acts->get();
    foreach ($acts as $act) {
        $start_time = $act->created_at;
        if ($act->event_type === Constants::JOINED) {
            $left = $acts->where('event_type', Constants::LEFT)->where('created_at', '>=', $start_time)->first();
            $end_time = now();

            if ($left !== NULL) {
                $end_time = $left->created_at;

            }
            $sum += $start_time->diffInMinutes($end_time);
        }
    }
    return [
        'activities' => $acts,
        'sum'        => $sum,
    ];
    //    if ($request->yesterday) {
    //
    //        $acts = $acts->where('created_at', '>=', today()->subDay())->where('created_at', '<=', today());
    //
    //
    //    }
    //
    //    if ($request->currentMonth) {
    //
    //        $acts = $acts->where('created_at', '>=', now()->firstOfMonth());
    //
    //
    //    }


    return api($sum);
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
