<?php

namespace App\Console\Commands;

use App\Http\Resources\RoomResource;
use App\Http\Resources\UserMinimalResource;
use App\Models\Room;
use App\Models\User;
use App\Utilities\Constants;
use Illuminate\Console\Command;

class CheckUsersOnline extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-users-online';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle() {
        $rooms = Room::whereNotNull('workspace_id')->get();
        foreach ($rooms as $room) {
            foreach ($room->lkUsers() as $lkUser) {

                $username = $lkUser->getIdentity();
                $user = $room->users->where('username', $username)->first();
                if ($user !== NULL) {
                    $last_activity = $user->lastActivity();
                    if ($last_activity === NULL) {
                        $user->activities()->create([
                                                        'join_at'      => now(),
                                                        'left_at'      => NULL,
                                                        'workspace_id' => $room->workspace->id,
                                                        'room_id'      => $room->id,
                                                        'data'         => NULL,
                                                    ]);
                    }


                } else {

                    $user = User::byUsername($username);
                    $room = $room->joinUser($user, FALSE);


                    $res = RoomResource::make($room);


                    sendSocket(Constants::roomUpdated, $room->channel, $res);

                    sendSocket(Constants::userJoinedToRoom, $room->workspace->channel, [
                        'room_id' => $room->id,
                        'user'    => UserMinimalResource::make($user)
                    ]);

                }


            }
        }

    }
}
