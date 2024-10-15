<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageListResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserMinimalResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\File;
use App\Models\Message;
use App\Models\Participant;
use App\Models\Room;
use App\Models\Seen;
use App\Models\User;
use App\Models\Workspace;
use App\Utilities\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller {

    public function send(Request $request) {
        $request->validate(['text' => 'required', 'chat_id' => 'required']);

        $user = auth()->user();


        $message = Message::create([
                                       'text'     => $request->text,
                                       'reply_to' => $request->reply_id,
                                       'user_id'  => $user->id,
                                       'chat_id'  => $request->chat_id,
                                       'nonce_id' => $request->nonce_id,
                                   ]);

        if ($request->mentions) {
            $models = ['user' => User::class, 'room' => Room::class, 'workspace' => Workspace::class,];
            foreach ($request->mentions as $mention) {
                $message
                    ->mentions()->create([
                                             'user_id'          => $user->id,
                                             'start_position'   => $mention['start_position'],
                                             'mentionable_type' => $models[$mention['model_type']],
                                             'mentionable_id'   => $mention['model_id']

                                         ]);
            }
        }

        if ($request->links) {
            foreach ($request->links as $link) {
                $message
                    ->links()->create([
                                          'start_position' => $link['start_position'],
                                          'url'            => $link['url'],
                                          'text'           => $link['text'],
                                      ]);
            }
        }


        if ($request->get('files')) {
            foreach ($request->get('files') as $file) {
                File::syncFile($file, $message);

            }
        }
        DB::table('chat_user')->where('user_id', $user->id)->where('chat_id', $message->chat_id)->update([
                                                                                                             'last_message_seen_id' => $message->id
                                                                                                         ]);

        return api(MessageResource::make($message));

    }

    public function seen(Message $message) {
        $user = auth()->user();


        DB::table('chat_user')->where('user_id', $user->id)->where('chat_id', $message->chat_id)->update([
                                                                                                             'last_message_seen_id' => $message->id
                                                                                                         ]);


        return api(TRUE);


    }

    public function searchMention(Request $request) {

        $users = User::where('username', 'LIKE', $request->q . '%')->get();
        $workspaces = Workspace::where('title', 'LIKE', $request->q . '%')->get();
        $rooms = Room::where('title', 'LIKE', $request->q . '%')->get();

        return api([
                       'users'      => UserMinimalResource::collection($users),
                       'workspaces' => WorkspaceResource::collection($workspaces),
                       'rooms'      => RoomResource::collection($rooms),
                   ]);

    }

    public function get(Room $room) {
        $user = auth()->user();
        //TODO check if user is in room

        $messages = $room->messages;

        return api(MessageListResource::collection($messages));

    }

    public function pin(Message $message) {
        //TODO: check user can pin message in this room

        $message->update(['is_pinned' => TRUE]);

        //        sendSocket(Constants::messagePinned, $message->room->channel, MessageResource::make($message));

    }

    public function unPin(Message $message) {
        //TODO: check user can pin message in this room

        $message->update(['is_pinned' => FALSE]);

        //        sendSocket(Constants::messageUnPinned, $message->room->channel, MessageResource::make($message));

    }


    public function delete(Message $message) {

        if (auth()->id() === $message->user_id) {
            $message->delete();

        }


        return api(TRUE);

    }

    public function update(Message $message, Request $request) {


        if (auth()->id() === $message->user_id) {
            $message->update(['text' => $request->text, 'is_edited' => TRUE]);


            File::syncFile($request->file_id, $message);

        }


        return api(MessageResource::make($message));


    }


}
