<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChatResource;
use App\Models\Chat;
use App\Models\Workspace;
use App\Utilities\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
                               'type'         => [
                                   'required',
                                   Rule::in([Constants::GROUP, Constants::CHANNEL, Constants::DIRECT])
                               ],
                               'title'        => 'required_if:type,group',
                               'participants' => 'required_if:type,group|required_if:workspace_id,null',
                               'user_id'      => 'required_if:type,direct',
                           ]);


        $user = $request->user();
        if ($request->type === 'direct') {


            $users = [$request->user_id, $user->id];
            asort($users);
            $title = implode('-', $users);

            $chat = Chat::whereTitle($title)->first();
            if ($chat !== NULL) {
                return error('Chat exists!');
            }


            $chat = Chat::create([
                                     'title'   => $title,
                                     'type'    => Constants::DIRECT,
                                     'user_id' => $user->id,
                                 ]);
        }


        if ($request->type === 'group') {
            $hasToAddParticipants = TRUE;

            if ($request->workspace_id !== NULL) {
                Workspace::findOrFail($request->workspace_id);

                //TODO: has to check user has permission to add group to workspace or not.

                $hasToAddParticipants = FALSE;
            }

            $chat = Chat::create([
                                     'title'        => $request->title,
                                     'type'         => Constants::GROUP,
                                     'user_id'      => $user->id,
                                     'workspace_id' => $request->workspace_id,
                                 ]);

            if ($hasToAddParticipants) {


                $participants = [];

                foreach ($request->participants as $participant) {
                    $participants[] = [$participant, ['role' => 'member']];
                }
                $participants[] = [$user->id, ['role' => 'super-admin']];

                $chat->users()->attach($participants);

            }


        }


        if ($request->type === 'channel') {

            if ($request->workspace_id !== NULL) {
                Workspace::findOrFail($request->workspace_id);
                //TODO: has to check user has permission to add channel to workspace or not.

            }

            $chat = Chat::create([
                                     'title'        => $request->title,
                                     'type'         => Constants::CHANNEL,
                                     'user_id'      => $user->id,
                                     'workspace_id' => $request->workspace_id,
                                 ]);

            $chat->users()->attach($user->id, ['role', 'super-admin']);


        }


        /** @var Chat $chat */
        return api(ChatResource::make($chat));


    }
}
