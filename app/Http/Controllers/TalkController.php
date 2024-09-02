<?php

namespace App\Http\Controllers;

use App\Http\Resources\TalkResource;
use App\Models\Talk;
use App\Models\User;
use App\Utilities\Constants;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TalkController extends Controller
{
    public function talk(Request $request)
    {
        $owner = auth()->user();


        $user = User::findOrFail($request->user_id);

        $talk = $owner->talks()->create([
                                            'user_id' => $user->id,
                                        ]);


        $response = TalkResource::make($talk);
        sendSocket(Constants::talkCreated, $user->socket_id, $response);

        return api($response);

    }


    /**
     * @throws \Exception
     */
    public function respond(Talk $talk, Request $request)
    {

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
