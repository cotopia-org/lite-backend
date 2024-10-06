<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\RoomServiceClient;
use App\Enums\Permission;
use App\Http\Resources\MessageResource;
use App\Http\Resources\RoomResource;
use App\Http\Resources\UserMinimalResource;
use App\Jobs\disconnectLivekitJob;
use App\Jobs\DisconnectUserJob;
use App\Models\File;
use App\Models\Room;
use App\Models\Seen;
use App\Models\Workspace;
use App\Utilities\Constants;
use Illuminate\Http\Request;

class RoomController extends Controller {
    public function update(Room $room, Request $request) {


        $user = auth()->user();

        $user->canDo(Permission::ROOM_UPDATE, $room->workspace->id);


        $room->update($request->all());

        File::syncFile($request->background_id, $room, 'background');
        File::syncFile($request->logo_id, $room, 'logo');

        //        sendSocket(Constants::roomUpdated, $room->channel, RoomResource::make($room));

        if ($request->background_id) {
            sendSocket(Constants::roomBackgroundChanged, $room->channel, RoomResource::make($room));

        }

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
        $user->canDo(Permission::WS_ADD_ROOMS, $workspace->id);

        $room = $workspace->rooms()->create([
                                                'title'   => $request->title,
                                                'user_id' => $user->id
                                            ]);

        $res = RoomResource::make($room);

        sendSocket(Constants::roomCreated, $workspace->channel, $res);
        return api($res);

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

        if ($user->status !== Constants::ONLINE && $user->socket_id === NULL) {
            return error('Not Connected');
        }

        $before_room = $user->room_id;


        if ($before_room !== NULL) {
            DisconnectUserJob::dispatch($user, FALSE, FALSE, 'Disconnected From RoomController Join method Due Change Room');
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


        $user->activities()->create([
                                        'join_at'      => now(),
                                        'left_at'      => NULL,
                                        'workspace_id' => $room->workspace->id,
                                        'room_id'      => $room->id,
                                        'data'         => 'Connected From RoomController Join Method',
                                    ]);


        return api($res);

    }


    public function delete(Room $room) {
        //TODO CHECK PERMISSION
        $user = auth()->user();
        $user->canDo(Permission::ROOM_DELETE, $room->workspace->id);

        foreach ($room->users as $user) {
            DisconnectUserJob::dispatch($room, $user, FALSE, FALSE, 'Disconnected From RoomController Delete Method');

        }


        $room->delete();

        return api(TRUE);


    }


    public function leave() {
        $user = auth()->user();
        $request = \request();


        DisconnectUserJob::dispatch($user, FALSE, FALSE, 'Disconnected From RoomController Leave Method');


        return TRUE;

    }


}
