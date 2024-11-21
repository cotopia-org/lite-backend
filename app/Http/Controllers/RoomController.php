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

class RoomController extends Controller
{
    public function update(Room $room, Request $request)
    {


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


    public function create(Request $request)
    {

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
                                                'user_id' => $user->id
                                            ]);

        $res = RoomResource::make($room);

        sendSocket(Constants::roomCreated, $workspace->channel, $res);
        return api($res);

    }

    public function get(Room $room)
    {
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

    public function join(Room $room)
    {
        $user = auth()->user();

        //        if (!$user->inSocket()) {
        //            return error('Not in Socket');
        //        } TODO: Commented due command checks every minute of user is in socket or not.

        $before_room = $user->room_id;


        if ($before_room === NULL) {
            // It means its first time for join.
            acted($user->id, $room->workspace_id, $room->id, $user->active_job_id, 'time_started',
                  'RoomController@join');

        }
        $room = $room->joinUser($user);


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


        joinUserToSocketRoom($user->id, $room->id);


        $user->joined($room, 'Connected From RoomController Join Method');


        return api($res);

    }


    public function delete(Room $room)
    {
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


    public function leave()
    {
        $user = auth()->user();
        $request = \request();


        DisconnectUserJob::dispatch($user, FALSE, FALSE, 'Disconnected From RoomController Leave Method');


        return TRUE;

    }


}
