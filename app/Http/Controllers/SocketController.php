<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\RoomServiceClient;
use App\Http\Resources\MessageResource;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserMinimalResource;
use App\Http\Resources\UserResource;
use App\Jobs\disconnectLivekitJob;
use App\Jobs\DisconnectUserJob;
use App\Models\Activity;
use App\Models\Message;
use App\Models\Room;
use App\Models\Seen;
use App\Models\User;
use App\Utilities\Constants;
use App\Utilities\EventType;
use Illuminate\Http\Request;

class SocketController extends Controller
{


    public function checkUser()
    {
        $user = auth()->user();
        return api([
                       'id'       => $user->id,
                       'username' => $user->username,
                   ]);
    }

    public function connected(Request $request)
    {

        $user = auth()->user();
        $user->update([
                          'socket_id' => $request->socket_id,
                          'status'    => Constants::ONLINE
                      ]);
        acted($user->id, NULL, NULL, $user->active_job_id, 'connected', 'SocketController@conected');

        return api([
                       'id'       => $user->id,
                       'username' => $user->username,
                       'channels' => $user->channels()
                   ]);
    }

    public function events(Request $request)
    {


        return TRUE;
        try {

            $event = new EventType($request->all());
            $user = $event->user();
            //            $room = $event->room();
            if ($user !== NULL) {


                if ($event->event === Constants::LEFT) {

                    //                    DisconnectUserJob::dispatch($user, FALSE, FALSE, 'Disconnected From SocketController Events Method');


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

    public function updateCoordinates(Request $request)
    {

        $user = auth()->user();


        if ($user !== NULL) {

            $user->update([
                              'coordinates' => $request->coordinates
                          ]);
        }

    }

    public function disconnected()
    {

        $user = auth()->user();
        $request = \request();


        if (!$user->isInSocket()) {

            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'time_ended',
                  'SocketController@disconnected');
            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'disconnected',
                  'SocketController@disconnected');

            DisconnectUserJob::dispatch($user, $request->offline !== NULL, FALSE,
                                        'Disconnected From SocketController Disconnected Method');

        }


        return TRUE;

    }

    public function logger(Request $request)
    {

        logger($request->all());

    }

}
