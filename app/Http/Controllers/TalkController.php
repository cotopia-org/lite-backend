<?php

namespace App\Http\Controllers;

use App\Http\Resources\TalkResource;
use App\Http\Resources\UserMinimalResource;
use App\Models\Talk;
use App\Models\User;
use App\Utilities\Constants;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TalkController extends Controller {
    public function talk(Request $request) {
        $owner = auth()->user();


        $user = User::findOrFail($request->user_id);

        $talk = Talk::create([
                                 'user_id'  => $user->id,
                                 'owner_id' => $owner->id,
                                 'type'     => $request->type,
                             ]);


        $response = TalkResource::make($talk);
        sendSocket(Constants::talkCreated, $user->socket_id, $response);

        return api($response);

    }


    public function expire(Talk $talk) {
        $talk->update([
                          'response' => Constants::NO_RESPONSE
                      ]);


        $user = $talk->user;
        $user->update([
                          'status' => Constants::GHOST,

                      ]);


        if ($user->room_id !== NULL) {
            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'time_ended', 'CheckTalkCommand@handle');
        }

        acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'disconnected', 'CheckTalkCommand@handle');

        if ($user->active_job_id !== NULL) {
            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended', 'CheckTalkCommand@handle');

        }
        $user->left('Disconnected for Ghost in CheckTalksCommand@handle');


        if ($user->room !== NULL) {
            sendSocket(Constants::userUpdated, $user->room->channel, UserMinimalResource::make($user));

        }


        sendSocket(Constants::talkExpired, $talk->owner->socket_id, TalkResource::make($talk));
        sendSocket(Constants::talkExpired, $talk->user->socket_id, TalkResource::make($talk));

        return true;
    }

    /**
     * @throws \Exception
     */
    public function respond(Talk $talk, Request $request) {

        $request->validate([
                               'response' => Rule::in([
                                                          Constants::ACCEPTED,
                                                          Constants::REJECTED,
                                                          Constants::LATER
                                                      ])
                           ]);

        if ($talk->response === Constants::NO_RESPONSE || auth()->id() !== $talk->user_id) {


            return error('Talk has been expired');
        }
        $talk->update(['response' => $request->response]);


        $response = TalkResource::make($talk);
        sendSocket(Constants::talkResponded, $talk->owner->socket_id, $response);
        return api($response);


    }


}
