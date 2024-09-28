<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Http\Resources\ChatResource;
use App\Http\Resources\JobResource;
use App\Http\Resources\RoomResource;
use App\Http\Resources\ScheduleResource;
use App\Http\Resources\TalkResource;
use App\Http\Resources\UserMinimalResource;
use App\Http\Resources\UserResource;
use App\Models\File;
use App\Models\Room;
use App\Models\User;
use App\Models\Workspace;
use App\Utilities\Constants;
use Illuminate\Http\Request;

class UserController extends Controller {
    public function me() {

        return api(UserResource::make(auth()->user()));
    }

    public function jobs(Request $request, $user) {
        if ($user === 'me') {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }
        $jobs = $user->jobs();
        if ($request->workspace_id) {
            $jobs = $jobs->orderBy('updated_at', 'DESC')->where('workspace_id', $request->workspace_id);
        }
        return api(JobResource::collection($jobs->get()));
    }


    public function workspaces() {
        $user = auth()->user();

        return api(JobResource::collection($user->workspaces()));
    }

    public function search(Request $request) {
        //TODO: have to use meiliserach instead
        $search = $request->search;
        $users = User::where(function ($query) use ($search) {
            $query
                ->where('name', 'LIKE', $search . '%')->orWhere('username', 'LIKE', $search . '%')
                ->orWhere('email', 'LIKE', $search . '%');
        })->get();
        return api(UserMinimalResource::collection($users));
    }

    public function updateCoordinates(Request $request) {
        $user = auth()->user();
        $request->validate([
                               'coordinates' => 'required'
                           ]);

        $user->update([
                          'coordinates' => $request->coordinates
                      ]);

        $response = UserMinimalResource::make($user);

        if ($user->room !== NULL) {
            sendSocket(Constants::userUpdated, $user->room->channel, $response);

        }
        return api($response);

    }

    public function toggleMegaphone() {
        $user = auth()->user();


        $user->update([
                          'is_megaphone' => !$user->is_megaphone
                      ]);

        $response = UserMinimalResource::make($user);
        if ($user->room !== NULL) {
            sendSocket(Constants::userUpdated, $user->room->channel, $response);

        }

        return api($response);

    }


    public function unGhost() {
        $user = auth()->user();
        $user->update([
                          'status' => Constants::ONLINE,
                      ]);
        $response = UserMinimalResource::make($user);

        sendSocket(Constants::userUpdated, $user->room->channel, $response);
        return api($response);

    }

    public function update(Request $request) {
        $user = auth()->user();
        $user->update([
                          'name'              => $request->name ?? $user->name,
                          'voice_status'      => $request->voice_status ?? $user->voice_status,
                          'video_status'      => $request->video_status ?? $user->video_status,
                          'livekit_connected' => $request->livekit_connected ?? $user->livekit_connected,
                      ]);

        File::syncFile($request->avatar_id, $user, 'avatar');
        $response = UserMinimalResource::make($user);

        if ($user->room !== NULL) {
            sendSocket(Constants::userUpdated, $user->room->channel, $response);

        }


        return api($response);
    }

    public function activities(Request $request) {

        return api(auth()->user()->getTime($request->period)['sum_minutes']);
    }

    public function chats(Request $request) {

        $user = auth()->user();
        $chats = $user->chats;


        $workspaces = $user->workspaces();

        if ($request->workspace_id) {
            $workspaces = $workspaces->find($request->workspace_id);
            $chats->merge($workspaces->chats);
        } else {
            $workspaceChats = $workspaces->get()->map(function ($workspace) use ($chats) {
                $chats = $chats->merge($workspace->chats);
            });

        }


        return api(ChatResource::collection($chats));
    }


    public function talks() {
        return api(TalkResource::collection(auth()->user()->talks));
    }

    public function schedules($user) {
        if ($user === 'me') {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }


        return api(ScheduleResource::collection($user->schedules));
    }


}
