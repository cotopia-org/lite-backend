<?php

namespace App\Console\Commands;

use App\Http\Resources\UserMinimalResource;
use App\Jobs\DisconnectUserJob;
use App\Utilities\Constants;
use Illuminate\Console\Command;

class CheckUsersInSocketCommand extends Command {
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
    public function handle() {
        $socket_users = getSocketUsers();

        $online_users = \App\Models\User::whereStatus('online')->whereNotNull('room_id')->get();
        foreach ($online_users as $user) {


            $socket_user = $socket_users->where('socket_id', $user->socket_id)->first();
            if ($socket_user === NULL) {

                DisconnectUserJob::dispatch($user, TRUE, TRUE, 'Disconnected From Command checkUsersInSocket');
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'time_ended', 'CheckUsersInSocketCommand@handle');
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'disconnected', 'CheckUsersInSocketCommand@handle');

            }
            if (!$user->isInLk() && $user->room_id !== NULL) {
                sendSocket(Constants::livekitDisconnected, $user->room->channel, UserMinimalResource::make($user));
            }


        }


    }
}
