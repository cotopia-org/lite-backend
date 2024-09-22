<?php

namespace App\Console\Commands;

use App\Http\Resources\RoomResource;
use App\Http\Resources\UserMinimalResource;
use App\Jobs\disconnectLivekitJob;
use App\Models\Room;
use App\Utilities\Constants;
use Illuminate\Console\Command;

class checkUsersInSocketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-users-in-socket-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $socket_users = collect(\Http::get('http://localhost:3010/sockets')->json());


        $online_users = \App\Models\User::whereStatus('online')->get();
        foreach ($online_users as $user) {


            $socket_user = $socket_users->where('username', $user->username)->first();
            if ($socket_user === NULL) {

                $room_id = $user->room_id;
                $user->update([
                                  'socket_id'    => NULL,
                                  'status'       => Constants::OFFLINE,
                                  'room_id'      => NULL,
                                  'workspace_id' => NULL,

                              ]);

                $room = Room::find($room_id);


                if ($room !== NULL) {


                    sendSocket(Constants::userLeftFromRoom, $room->workspace->channel, [
                        'room_id' => $room_id,
                        'user'    => UserMinimalResource::make($user)
                    ]);

                    sendSocket(Constants::workspaceRoomUpdated, $room->workspace->channel, RoomResource::make($room));


                    disconnectLivekitJob::dispatch($room, $user)->delay(10);


                }
                $user->left();
            }
        }
    }
}
