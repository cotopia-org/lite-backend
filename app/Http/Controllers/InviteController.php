<?php

namespace App\Http\Controllers;

use App\Http\Resources\InviteResource;
use App\Models\Invite;
use App\Models\Job;
use App\Models\Room;
use App\Models\Workspace;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function invite(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'user_id'  => 'nullable|integer|exists:users,id',
            'model_id' => 'required|integer',
            'model'    => 'required',
        ]);

        $models = [
            'job'       => Job::class,
            'room'      => Room::class,
            'workspace' => Workspace::class,
        ];

        $invite = Invite::create([
            'owner_id'        => $user->id,
            'user_id'         => $request->user_id,
            'inviteable_type' => $models[$request->model],
            'inviteable_id'   => $request->model_id,
            'status'          => 'pending',

        ]);

        return api(InviteResource::make($invite));

    }

    public function get($code)
    {
        /** @var Invite $invite */
        $invite = Invite::findByCode($code);

        $user = auth()->user();
        if ($invite->user_id && ! $invite->isForUser($user)) {

            return error('Invite code expired');
        }

        return api(InviteResource::make($invite));

    }

    public function decline($code)
    {
        /** @var Invite $invite */
        $invite = Invite::findByCode($code);
        $user = auth()->user();
        if ($invite->user_id && $invite->isForUser($user)) {

            return error('Invite code expired');
        }

        $invite->status = 'declined';
        $invite->save();

        return api(InviteResource::make($invite));
    }

    public function join($code)
    {
        /** @var Invite $invite */
        $invite = Invite::findByCode($code);

        $user = auth()->user();
        if ($invite->status !== 'pending' || ($invite->user_id && $invite->isForUser($user))) {

            return error('Invite code expired');
        }

        if ($invite->inviteable instanceof Room) {
            $invite->inviteable->workspace->joinUser($user);
        }
        $inviteable = $invite->inviteable->joinUser($user);
        $invite->status = 'joined';
        $invite->save();

        return api($invite->getResponseModel($inviteable));

    }
}
