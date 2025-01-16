<?php

namespace App\Console\Commands;

use App\Http\Resources\TalkResource;
use App\Http\Resources\UserMinimalResource;
use App\Http\Resources\UserResource;
use App\Models\Talk;
use App\Utilities\Constants;
use Illuminate\Console\Command;

class CheckTalksCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-talks-command';

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
        //TODO: change 1 to 3 for production
        $talks = Talk::where('created_at', '<=', now()->subMinutes(1))->whereNull('response')->get();


        foreach ($talks as $talk) {

            $talk->update([
                              'response' => Constants::NO_RESPONSE
                          ]);


            $user = $talk->user;
            $user->update([
                              'status' => Constants::GHOST,

                          ]);


            if ($user->room_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'time_ended', 'CheckTalkCommand@handle');
            }

            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'disconnected', 'CheckTalkCommand@handle');

            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended', 'CheckTalkCommand@handle');

            }
            $user->left('Disconnected for Ghost in CheckTalksCommand@handle');


            if ($user->room !== NULL) {
                sendSocket(Constants::userUpdated, $user->room->channel, UserMinimalResource::make($user));

            }


            sendSocket(Constants::talkExpired, $talk->owner->socket_id, TalkResource::make($talk));
            sendSocket(Constants::talkExpired, $talk->user->socket_id, TalkResource::make($talk));

        }
    }
}
