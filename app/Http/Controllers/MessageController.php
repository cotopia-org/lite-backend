<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageListResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserMinimalResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\File;
use App\Models\Message;
use App\Models\Room;
use App\Models\Seen;
use App\Models\User;
use App\Models\Workspace;
use App\Utilities\Constants;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function send(Request $request)
    {
        $request->validate(['text' => 'required']);

        $user = auth()->user();

        if ($request->room_id === NULL) {
            $request->validate(['user_id' => 'required']);


            $users = [$request->user_id, $user->id];
            asort($users);
            $roomTitle = implode('-', $users);


            $room = Room::firstOrCreate(['title' => $roomTitle], ['is_private' => TRUE]);

        } else {
            $room = Room::findOrFail($request->room_id);
            //            if (!$room->workspace->hasUser($user)) {
            //                return error('You are not authorized');
            //            }
        }


        $message = Message::create([
                                       'text'     => $request->text,
                                       'reply_to' => $request->reply_to,
                                       'user_id'  => $user->id,
                                       'room_id'  => $room->id,
                                       'nonce_id' => $request->nonce_id,
                                   ]);

        if ($request->mentions) {
            $models = ['user' => User::class, 'room' => Room::class, 'workspace' => Workspace::class,];
            foreach ($request->mentions as $mention) {
                $message->mentions()->create([
                                                 'user_id'          => $user->id,
                                                 'start_position'   => $mention['start_position'],
                                                 'mentionable_type' => $models[$mention['model_type']],
                                                 'mentionable_id'   => $mention['model_id']

                                             ]);
            }
        }

        if ($request->links) {

            foreach ($request->links as $link) {
                $message->links()->create([
                                              'start_position' => $link['start_position'],
                                              'url'            => $link['url'],
                                              'text'           => $link['text'],
                                          ]);
            }
        }

        $messageResponse = MessageResource::make($message);
        //EMIT TO USER
//        sendSocket($room->isDirectRoom() ? Constants::directMessages : Constants::roomMessages, $room->channel,
//                   $messageResponse);

        //Commened due changed to socket.

        Seen::firstOrCreate(['user_id' => $user->id, 'room_id' => $room->id, 'message_id' => $message->id]);


        // FOR SHITTY UNSEEN.
        //        sendSocket(Constants::roomUpdated, $room->channel, RoomResource::make($room));


        if ($request->get('files')) {
            foreach ($request->get('files') as $file) {
                File::syncFile($file, $message);

            }
        }


        return api($messageResponse);

    }

    public function seen(Message $message)
    {
        $user = auth()->user();
        $room = $message->room;
        //        if (!$room->participants()->contains('id', $user->id)) {
        //            return error('You cant seen this message');
        //        }

        Seen::firstOrCreate(['user_id' => $user->id, 'room_id' => $room->id, 'message_id' => $message->id]);

        //        sendSocket(Constants::messageSeen, $message->room->channel, MessageResource::make($message));


        return api(TRUE);


    }

    public function searchMention(Request $request)
    {

        $users = User::where('username', 'LIKE', $request->q . '%')->get();
        $workspaces = Workspace::where('title', 'LIKE', $request->q . '%')->get();
        $rooms = Room::where('title', 'LIKE', $request->q . '%')->get();

        return api([
                       'users'      => UserMinimalResource::collection($users),
                       'workspaces' => WorkspaceResource::collection($workspaces),
                       'rooms'      => RoomResource::collection($rooms),
                   ]);

    }

    public function get(Room $room)
    {
        $user = auth()->user();
        //TODO check if user is in room

        $messages = $room->messages;

        return api(MessageListResource::collection($messages));

    }

    public function pin(Message $message)
    {
        //TODO: check user can pin message in this room

        $message->update(['is_pinned' => TRUE]);

        sendSocket(Constants::messagePinned, $message->room->channel, MessageResource::make($message));

    }

    public function unPin(Message $message)
    {
        //TODO: check user can pin message in this room

        $message->update(['is_pinned' => FALSE]);

        sendSocket(Constants::messageUnPinned, $message->room->channel, MessageResource::make($message));

    }


    public function delete(Message $message)
    {
        //TODO: check user owned msg
        $message->delete();


        $res = MessageResource::make($message);
        sendSocket(Constants::messageDeleted, $message->room->channel, $res);


        return api($res);

    }

    public function update(Message $message, Request $request)
    {
        //        $user = auth()->user();

        //TODO: check user owned msg
        $message->update(['text' => $request->text, 'is_edited' => TRUE]);


        File::syncFile($request->file_id, $message);


        $res = MessageResource::make($message);
        sendSocket(Constants::messageUpdated, $message->room->channel, $res);

        return api($res);


    }


}
