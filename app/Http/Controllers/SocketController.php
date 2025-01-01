<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\RoomServiceClient;
use App\Http\Resources\MessageResource;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserMinimalResource;
use App\Http\Resources\UserResource;
use App\Jobs\disconnectLivekitJob;
use App\Jobs\DisconnectUserJob;
use App\Models\Act;
use App\Models\Activity;
use App\Models\Message;
use App\Models\Room;
use App\Models\Seen;
use App\Models\User;
use App\Utilities\Constants;
use App\Utilities\EventType;
use Illuminate\Http\Request;

class SocketController extends Controller {


    public function checkUser() {
        $user = auth()->user();
        return api([
                       'id'       => $user->id,
                       'username' => $user->username,
                   ]);
    }

    public function connected(Request $request) {

        $user = auth()->user();

        if ($user->status === Constants::ONLINE) {


            if ($user->room_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'time_ended', 'User Was Online SocketController@connected');
            }

            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'disconnected', 'User Was Online SocketController@connected');

            DisconnectUserJob::dispatch($user, $request->offline !== NULL, FALSE, 'User Was Online Disconnected From SocketController Connected Method');

        }

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

    public function events(Request $request) {
        $req = json_decode(json_encode($request->all()));

        if (isset($req->room) && $req->room !== NULL) {
            sendSocket(Constants::livekitEvent, 'room-' . $req->room->name, $req);

        }

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
        $request = \request();

        $lastAct = Act::where('user_id', $user->id)->where('type', 'connected')->orderBy('id', 'desc')->first();

        if ($lastAct->created_at->gte(now())) {

            logger('NOW ' . now()->toDateTimeString());
            logger('CREATED AT ' . $lastAct->created_at->toDateTimeString());
            logger('MUST IGNORE');
            return TRUE;
        }
        //        logger($request->socket_status);
        if ($request->socket_status === 'enable') {

            if ($user->room_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'time_ended', 'SocketController@disconnected');
            }

            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'disconnected', 'SocketController@disconnected');

            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended', 'SocketController@disconnected');

            }
            DisconnectUserJob::dispatch($user, $request->offline !== NULL, FALSE, 'Disconnected From SocketController Disconnected Method');

        }


        return TRUE;

    }

    public function logger(Request $request) {
        return TRUE;
        //        $json = json_encode($request->all());
        //
        //        logger($json);

        //        $message = '';
        //        foreach ($request->all() as $req) {
        //            $message
        //        }
        //        sendMessage($json, 48);

    }

}
