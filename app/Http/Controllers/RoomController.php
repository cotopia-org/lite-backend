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

//                File::syncFile($request->background_id, $room, 'background');
        //        File::syncFile($request->logo_id, $room, 'logo');


        if ($request->background) {
            sendSocket(Constants::roomBackgroundChanged, $room->channel, RoomResource::make($room));

        } else {
            sendSocket(Constants::roomUpdated, $room->channel, RoomResource::make($room));

        }

        return api(RoomResource::make($room));

    }


    public function create(Request $request) {

        $request->validate([
                               'workspace_id' => 'required',
                               'title'        => 'required',
                           ]);

        //TODO:check has create room permission
        $user = auth()->user();


        $workspace = Workspace::findOrFail($request->workspace_id);


        $user->canDo(Permission::WS_ADD_ROOMS, $workspace->id);

        $room = $workspace->rooms()->create([
                                                'title'   => $request->title,
                                                'user_id' => $user->id,
                                                'type'    => $request->type ?? 'flow',
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


    public function switch(Room $room) {
        $user = auth()->user();


        $previous_room = $user->room;


        $room = $room->joinUser($user);


        $userResource = UserMinimalResource::make($user);

        sendSocket(Constants::userLeftFromRoom, $previous_room->workspace->channel, [
            'room_id' => $previous_room->id,
            'user'    => $userResource
        ]);

        sendSocket(Constants::userJoinedToRoom, $room->workspace->channel, [
            'room_id' => $room->id,
            'user'    => $userResource
        ]);


        userJoinedToRoomEmit($user->id, $room->id);


        return api(RoomResource::make($room));
    }

    public function join(Room $room) {
        $user = auth()->user();


        if ($user->workspaces()->find($room->workspace_id) === NULL) {
            return error('Sorry youre not in this workspace');
        }


        $before_room = $user->room_id;
        if ($user->status !== Constants::ONLINE) {
            return error('Not Online');
        }


        $time_start = TRUE;


        if ($user->activeContract() !== NULL) {
            if ($user->activeContract()->in_schedule && !isNowInUserSchedule($user->activeContract()->schedule)) {
                $time_start = FALSE;

            }
        } else {
            $time_start = FALSE;

        }


        if ($before_room === NULL && $time_start) {
            // It means its first time for join.
            acted($user->id, $room->workspace_id, $room->id, $user->active_job_id, 'time_started', 'RoomController@join');
            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_started', 'RoomController@join');

            }

        }
        $room = $room->joinUser($user);

        $room->time_start = $time_start;

        $res = RoomResource::make($room);

        $userResource = UserMinimalResource::make($user);
        if ($before_room !== NULL) {
            $before_room = Room::find($before_room);

            sendSocket(Constants::userLeftFromRoom, $before_room->workspace->channel, [
                'room_id' => $before_room->id,
                'user'    => $userResource
            ]);
        }

        sendSocket(Constants::userJoinedToRoom, $room->workspace->channel, [
            'room_id' => $room->id,
            'user'    => $userResource
        ]);


        userJoinedToRoomEmit($user->id, $room->id);


        if ($time_start) {
            $user->joined($room, 'Connected From RoomController Join Method');

        }


        return api($res);

    }


    public function delete(Room $room) {
        //TODO CHECK PERMISSION
        $user = auth()->user();
        $user->canDo(Permission::WS_ADD_ROOMS, $room->workspace->id);

        foreach ($room->users as $user) {
            DisconnectUserJob::dispatch($user, FALSE, FALSE, 'Disconnected From RoomController Delete Method');

        }


        $room->delete();

        sendSocket(Constants::roomDeleted, $room->workspace->channel, $room->id);

        return api(TRUE);


    }


    public function leave() {
        $user = auth()->user();
        $request = \request();


        DisconnectUserJob::dispatch($user, FALSE, FALSE, 'Disconnected From RoomController Leave Method');


        return TRUE;

    }


}
