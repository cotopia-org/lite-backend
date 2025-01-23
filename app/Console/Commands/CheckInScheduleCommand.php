<?php

namespace App\Console\Commands;

use App\Http\Resources\UserMinimalResource;
use App\Jobs\DisconnectUserJob;
use App\Models\User;
use App\Utilities\Constants;
use Illuminate\Console\Command;

class CheckInScheduleCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-in-schedule-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check users are in their schedule or not.';

    /**
     * Execute the console command.
     */
    public function handle() {
        $online_users = User::whereStatus('online')->whereNotNull('room_id')->get();
        foreach ($online_users as $user) {

            $activeContract = $user->activeContract();


            if ($activeContract === NULL) {
                $this->stop($user);

            } elseif ($activeContract->in_schedule) {

                if (isNowInUserSchedule($activeContract->schedule)) {
                    $this->start($user);

                } else {
                    $this->stop($user);

                }
            }


        }
    }


    public function stop($user) {


        $left = $user->left('Disconnected From checkInScheduleCommand stop Method');
        if ($left) {
            logger("User $user->id STOP");

            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'time_ended', 'CheckInScheduleCommand@stop');


            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended', 'CheckInScheduleCommand@stop');

            }
            sendSocket('timeStopped', $user->socket_id, $user->id);

        }
        return TRUE;

    }

    public function start($user) {


        $start = $user->joined($user->room, 'Connected From checkInScheduleCommand start Method');
        if ($start) {
            logger("User $user->id START");

            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'time_started', 'CheckInScheduleCommand@start');
            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_started', 'CheckInScheduleCommand@start');

            }
            sendSocket('timeStarted', $user->socket_id, $user->id);

        }
        return TRUE;


    }
}
