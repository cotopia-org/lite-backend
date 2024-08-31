<?php

namespace App\Jobs;

use Agence104\LiveKit\RoomServiceClient;
use App\Models\Room;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class disconnectLivekitJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct(public Room $room, public User $user) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void {

        logger('Here,Queue works');
        $room_id = $this->room->id;
        if ($this->room->isUserInLk($this->user)) {
            try {
                $host = config('livekit.host');
                $svc = new RoomServiceClient($host, config('livekit.apiKey'), config('livekit.apiSecret'));
                $svc->removeParticipant("$room_id", $this->user->username);
            } catch (\Exception $e) {
                logger($e);
            }
        }
    }
}
