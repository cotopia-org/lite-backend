<?php

namespace App\Console\Commands;

use App\Http\Resources\RoomResource;
use App\Http\Resources\UserMinimalResource;
use App\Jobs\disconnectLivekitJob;
use App\Jobs\DisconnectUserJob;
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
        logger('check DC COMMAND');


        $online_users = \App\Models\User::whereStatus('online')->whereNotNull('room_id')->get();
        foreach ($online_users as $user) {


            $socket_user = $socket_users->where('username', $user->username)->first();
            logger($socket_user);
            if ($socket_user === NULL) {

                DisconnectUserJob::dispatch($user, TRUE, TRUE, 'Disconnected From Command checkUsersInSocket');

            }
            if (!$user->isInLk() && $user->room_id !== NULL) {
                sendSocket(Constants::livekitDisconnected, $user->room->channel, UserMinimalResource::make($user));
            }


        }
        logger('Finished');
        logger('Tester');

    }
}
