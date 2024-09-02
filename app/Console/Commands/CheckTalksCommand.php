<?php

namespace App\Console\Commands;

use App\Http\Resources\TalkResource;
use App\Models\Talk;
use App\Utilities\Constants;
use Illuminate\Console\Command;

class CheckTalksCommand extends Command
{
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
    public function handle()
    {
        $talks = Talk::where('created_at', '<=', now()->subMinutes(3))->whereNull('response')->get();


        foreach ($talks as $talk) {

            $talk->update([
                              'response' => Constants::NO_RESPONSE
                          ]);


            sendSocket(Constants::talkExpired, $talk->owner->socket_id, TalkResource::make($talk));

        }
    }
}
