<?php

namespace App\Listeners;

use App\Events\ChatCreated;
use App\Http\Resources\ChatResource;
use App\Utilities\Constants;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;

class SendChatCreatedEvent {


    /**
     * Handle the event.
     */
    public function handle(ChatCreated $event): void {
        $chat = $event->chat;


        $resource = ChatResource::make($chat);
        $eventName = Constants::chatCreated;
        foreach ($chat->users as $user) {

            sendSocket($eventName, $user->socket_id, $resource);
            Redis::publish('chat-created', json_encode([
                                                           'user_id' => $user->id,
                                                           'chat_id' => $chat->id
                                                       ], JSON_THROW_ON_ERROR));
        }
    }
}
