<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Events\ChatCreated;
use App\Http\Resources\ChatResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserMinimalResource;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use App\Utilities\Constants;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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


        event(new ChatCreated($chat));


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


            $user->canDo(Permission::WS_CREATE_CHAT, $request->workspace_id);


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
        event(new ChatCreated($chat));

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


    public function readMessages(Chat $chat, Request $request) {
        $user = auth()->user();


        $date = $request->date !== NULL ? Carbon::parse($request->date) : today()->subDays(7);
        $pivot = $chat->users()->find($user)->pivot;
        $joined_at = $pivot->created_at;
        $last_seen_message = $pivot->last_message_seen_id;


        if ($date->lt($joined_at)) {
            $date = $joined_at;
        }
        $groupedMessages = $chat
            ->messages()->with([
                                   'links',
                                   'mentions',
                                   'user',
                                   'files',
                               ])->where('created_at', '>=', $date)->where('id', '<=', $last_seen_message)->get()
            ->groupBy(function ($message) {
                return $message->created_at->format('Y-m-d'); // Group by date
            });


        $data = [];

        foreach ($groupedMessages as $date => $messages) {
            $data[$date] = MessageResource::collection($messages);
        }
        return api($data);
    }

    public function unreadMessages(Chat $chat) {
        $user = auth()->user();


        $pivot = $chat->users()->find($user)->pivot;
        $joined_at = $pivot->created_at;
        $last_seen_message = $pivot->last_message_seen_id;
        $groupedMessages = $chat
            ->messages()->with([
                                   'links',
                                   'mentions',
                                   'user',
                                   'files',
                               ])->where('id', '>', $last_seen_message)->where('created_at', '>=', $joined_at)->get()
            ->groupBy(function ($message) {
                return $message->created_at->format('Y-m-d'); // Group by date
            });


        $data = [];

        foreach ($groupedMessages as $date => $messages) {
            $data[$date] = MessageResource::collection($messages);
        }
        return api($data);
    }

    public function messages(Chat $chat) {


        $user = auth()->user();


        $pivot = $chat->users()->find($user)->pivot;
        $joined_at = $pivot->created_at;
        $messages = $chat
            ->messages()->orderBy('id', 'DESC')->with([
                                                          'links',
                                                          'mentions',
                                                          'user',
                                                          'files',
                                                      ])->where('created_at', '>=', $joined_at)->paginate(50);

        return api(MessageResource::collection($messages));

    }

    public function toggleMute(Chat $chat) {
        $user = auth()->user();

        $user_chat = DB::table('chat_user')->where('user_id', $user->id)->where('chat_id', $chat->id)->first();
        if ($user_chat === NULL) {
            return error('You are not participants of this chat');
        }


        DB::table('chat_user')->where('user_id', $user->id)->where('chat_id', $chat->id)->update([
                                                                                                     'muted' => !$user_chat->muted
                                                                                                 ]);

        return api(TRUE);
    }

    public function delete(Chat $chat) {
        $user = auth()->user();
        $user->canDo(Permission::WS_DELETE_CHAT, $user->workspace_id);

        $user = auth()->user();
        $chat->delete();
        return api(TRUE);
    }


    public function setFolder(Chat $chat, Request $request) {
        $user = auth()->user();

        $chatUser = DB::table('chat_user')->where('user_id', $user->id)->where('chat_id', $chat->id)->first();

        if ($chatUser === NULL) {
            return error('You are not participants of this chat');
        }


        $chatUser->update([
                              'folder_id' => $request->folder_id
                          ]);

        return api(TRUE);

    }

    public function get(Chat $chat) {


        return api(ChatResource::make($chat));

    }
}
