<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\RoomServiceClient;
use App\Http\Resources\UserResource;
use App\Models\Activity;
use App\Models\User;
use App\Utilities\Constants;
use App\Utilities\EventType;
use Illuminate\Http\Request;

class SocketController extends Controller {
    public function connected(Request $request) {

        $user = auth()->user();
        $user->update([
                          'socket_id' => $request->socket_id,
                          'status'    => Constants::ONLINE
                      ]);


        return api(UserResource::make(auth()->user()));
    }

    public function events(Request $request) {

        try {

            $event = new EventType($request->all());
            $user = $event->user();
            $room = $event->room();
            if ($user !== NULL) {


                if ($event->event === Constants::JOINED || $event->event === Constants::LEFT) {
                    $last_activity = $user->activities()->whereNull('left_at')->first();
                    if ($last_activity !== NULL) {
                        $last_activity->update([
                                                   'left_at' => now(),
                                                   'data'    => json_encode($request->all()),

                                               ]);
                    }

                    $user->left(json_encode($request->all()));

                    if ($event->event === Constants::JOINED) {


                        $event->user()->activities()->create([
                                                                 'join_at'      => now(),
                                                                 'left_at'      => NULL,
                                                                 'workspace_id' => $room->workspace->id,
                                                                 'room_id'      => $room->id,
                                                                 'data'         => json_encode($request->all()),
                                                             ]);


                    }
                }

            }

        } catch (\Exception $e) {
            logger($e);
            logger($request->all());
        }

        ////
        //        if ($event->hasParticipant()) {
        //
        //            if ($event->event === Constants::JOINED) {
        //                if ($event->user()->room_id === null) {
        //                    $event->user()->update([
        //                        'room_id' => $event->room()->id,
        //                        'workspace_id' => $event->room()->workspace->id,
        //                    ]);
        //                }
        //
        //            }
        ////            if ($event->event === Constants::LEFT) {
        ////                $event->user()->update([
        ////                    'room_id' => null,
        ////                    'workspace_id' => null,
        ////                ]);
        ////
        ////            }
        //
        //        }


    }


    public function updateCoordinates(Request $request) {

        $user = auth()->user();


        if ($user !== NULL) {

            $user->update([
                              'coordinates' => $request->coordinates
                          ]);
        }

    }

    public function disconnected() {

        $user = auth()->user();

        $room_id = $user->room_id;
        $user->update([
                          'socket_id'    => NULL,
                          'status'       => Constants::OFFLINE,
                          'room_id'      => NULL,
                          'workspace_id' => NULL,

                      ]);
        $user->left();
        //TODO: has to check is lk user in room or not before request

        try {
            $host = config('livekit.host');
            $svc = new RoomServiceClient($host, config('livekit.apiKey'), config('livekit.apiSecret'));
            $svc->removeParticipant("$room_id", $user->username);
        } catch (\Exception $e) {
            //            logger($e);
        }


        return api(UserResource::make(auth()->user()));
    }
}
