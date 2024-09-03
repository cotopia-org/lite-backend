<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\RoomServiceClient;
use App\Http\Resources\MessageResource;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserMinimalResource;
use App\Models\File;
use App\Models\Room;
use App\Models\Seen;
use App\Models\Workspace;
use App\Utilities\Constants;
use Illuminate\Http\Request;

class RoomController extends Controller {
    public function update(Room $room, Request $request) {
        //TODO CHECK PERMISSION
        $room->update($request->all());

        File::syncFile($request->background_id, $room, 'background');
        File::syncFile($request->logo_id, $room, 'logo');

        sendSocket(Constants::roomUpdated, $room->channel, RoomResource::make($room));

        return api(RoomResource::make($room));

    }


    public function create(Request $request) {


        $request->validate([
                               'workspace_id' => 'required',
                               'title'        => 'required',
                           ]);

        //TODO:check has create room permission

        $workspace = Workspace::findOrFail($request->workspace_id);
        $user = auth()->user();

        $room = $workspace->rooms()->create([
                                                'title'   => $request->title,
                                                'user_id' => $user->id
                                            ]);


        return api(RoomResource::make($room));

    }

    public function get(Room $room) {
        //        $user = auth()->user();
        //        $workspace = $user->workspaces()->find($workspace);
        //        if ($workspace === NULL) {
        //            return error('You have no access to this workspace');
        //
        //        }
        //
        //        $room = $workspace->rooms()->findOrFail($room);

        return api(RoomResource::make($room));
    }

    public function join(Room $room) {
        $user = auth()->user();


        $before_room = $user->room_id;


        if ($user->room_id !== NULL) {
            try {
                $host = config('livekit.host');
                $svc = new RoomServiceClient($host, config('livekit.apiKey'), config('livekit.apiSecret'));
                $svc->removeParticipant("$user->room_id", $user->username);
            } catch (\Exception $e) {

            }

        }

        $room = $room->joinUser($user);


        $res = RoomResource::make($room);

        if ($before_room !== NULL) {
            $before_room = Room::find($before_room);
            sendSocket(Constants::roomUpdated, $before_room->channel, RoomResource::make($before_room));
            sendSocket(Constants::workspaceRoomUpdated, $before_room->workspace->channel, RoomResource::make($before_room));

            sendSocket(Constants::userLeftFromRoom, $before_room->workspace->channel, [
                'room_id' => $before_room->id,
                'user'    => UserMinimalResource::make($user)
            ]);
        }

        sendSocket(Constants::roomUpdated, $room->channel, $res);
        sendSocket(Constants::workspaceRoomUpdated, $room->workspace->channel, $res);


        sendSocket(Constants::userJoinedToRoom, $room->workspace->channel, [
            'room_id' => $room->id,
            'user'    => UserMinimalResource::make($user)
        ]);


        return api($res);

    }

    public function messages(Room $room) {
        $user = auth()->user();

        $messages = $room->messages()->withTrashed()->with([
                                                               'links',
                                                               'mentions',
                                                               'user',
                                                               'files',
                                                           ])->orderByDesc('id')->paginate(\request('perPage', 10));


        return api(MessageResource::collection($messages));
    }


}
