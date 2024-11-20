<?php

namespace App\Jobs;

use App\Http\Resources\RoomResource;
use App\Http\Resources\UserMinimalResource;
use App\Models\Room;
use App\Models\User;
use App\Utilities\Constants;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DisconnectUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public User    $user, public bool $offline = FALSE, public bool $checkIsInRoom = FALSE,
                                public ?string $data)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $user = $this->user;

        if ($this->checkIsInRoom) {
            sleep(15);
            $socket_users = getSocketUsers();

            $socket_user = $socket_users->where('username', $user->username)->first();
            if ($socket_user === NULL) {

                self::dispatch($user, TRUE, FALSE, $this->data);
            }

        } else {
            $user->left($this->data);

            $room_id = $user->room_id;

            $user->update([
                              'socket_id'    => $this->offline ? NULL : $user->socket_id,
                              'status'       => $this->offline ? Constants::OFFLINE : $user->status,
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


//                disconnectLivekitJob::dispatch($room, $user);
                //commented due problem was in refresh

            }
        }


    }
}
