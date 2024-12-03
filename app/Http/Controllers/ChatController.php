<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChatResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserMinimalResource;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use App\Utilities\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ChatController extends Controller {

    public function createDirect(Request $request) {
        $request->validate([


                               'user_id' => 'required|exists:users,id',
                           ]);


        $user = $request->user();

        $users = [$request->user_id, $user->id];
        asort($users);
        $title = implode('-', $users);

        $chat = Chat::whereTitle($title)->first();
        if ($chat !== NULL) {
            return api(ChatResource::make($chat));

        }


        $chat = Chat::create([
                                 'title'   => $title,
                                 'type'    => Constants::DIRECT,
                                 'user_id' => $user->id,
                             ]);


        $chat->users()->attach($users);
        return api(ChatResource::make($chat));

    }

    public function createGroup(Request $request) {
        $request->validate([

                               'title' => 'required',
                           ]);


        $user = $request->user();

        $hasToAddParticipants = TRUE;

        if ($request->workspace_id !== NULL) {
            $workspace = Workspace::findOrFail($request->workspace_id);

            //TODO: has to check user has permission to add group to workspace or not.


            $request->participants = $workspace->users->pluck('id');
        } else {
            $request->validate([
                                   'participants' => 'required',
                               ]);

        }

        $chat = Chat::create([
                                 'title'        => $request->title,
                                 'type'         => Constants::GROUP,
                                 'user_id'      => $user->id,
                                 'workspace_id' => $request->workspace_id,
                             ]);

        $participants = [];
        foreach ($request->participants as $participant) {
            $role = 'member';
            if ($participant === $user->id) {
                $role = 'super-admin';
            }
            $participants[$participant] = ['role' => $role];
        }
        //            $participants[] = [$user->id, ['role' => 'super-admin']];
        $chat->users()->attach($participants);

        return api(ChatResource::make($chat));

    }


    public function createChannel(Request $request) {
        return error('Cant create channels yet.');

    }


    public function mentionedMessages(Chat $chat) {
        $user = auth()->user();
        return api(MessageResource::collection($chat->mentionedMessages($user)));

    }

    public function participants(Chat $chat) {

        return api(UserMinimalResource::collection($chat->users));

    }

    public function pinnedMessages(Chat $chat) {

        return api(MessageResource::collection($chat->pinnedMessages()));

    }

    public function sawMessages(Chat $chat) {
        $user = auth()->user();

        $messages = $chat->sawMessages($user)->paginate($request->perPage ?? 50);

        return api(MessageResource::collection($messages));

    }

    public function unseenMessages(Chat $chat) {
        $user = auth()->user();

        $messages = $chat->unSeens($user);
        return api(MessageResource::collection($messages));


    }


    public function getLastUnSeenMessagePage(Chat $chat) {
        $perPage = 50;

        $user = auth()->user();

        $pivot = $chat->users->find($user->id)->pivot;
        $last_message_seen_id = $pivot->last_message_seen_id ?? 0;

        $messagePosition = $chat->messages()->where('id', '<=', $last_message_seen_id)->count();


        return api([
                       'page_number' => ceil($messagePosition / $perPage)
                   ]);
    }

    public function getMessagePage(Chat $chat, Message $message) {
        $perPage = 50;

        $user = auth()->user();


        $messagePosition = $chat->messages()->where('id', '<=', $message->id)->count();


        return api([
                       'page_number' => ceil($messagePosition / $perPage)
                   ]);
    }

    public function messages(Chat $chat) {


        $user = auth()->user();


        $pivot = $chat->users()->find($user)->pivot;
        $joined_at = $pivot->created_at;
        $messages = $chat
            ->messages()->orderBy('id', 'DESC')->withTrashed()->with([
                                                                         'links',
                                                                         'mentions',
                                                                         'user',
                                                                         'files',
                                                                     ])->where('created_at', '>=', $joined_at)
            ->paginate(50);

        return api(MessageResource::collection($messages));

    }


    public function delete(Chat $chat) {

        $user = auth()->user();
        $chat->users()->detach($user->id);
        return api(TRUE);
    }
}
