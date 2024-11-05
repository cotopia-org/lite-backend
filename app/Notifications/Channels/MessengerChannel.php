<?php

namespace App\Notifications\Channels;

use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Utilities\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use RuntimeException;

class MessengerChannel {
    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return Model
     */
    public function send(object $notifiable, Notification $notification) {
        $data = $notification->toMessenger($notifiable);
        $notifUser = User::find(41);
        $title = $notifUser->id . '-' . $notifiable->id;
        $chat = Chat::whereTitle($title)->first();
        if ($chat === NULL) {
            $chat = Chat::create([
                                     //                                     'title'   => 'Lite Notifications',
                                     'title'   => $title,
                                     'type'    => Constants::DIRECT,
                                     'user_id' => $notifiable->id,
                                 ]);
            $chat->users()->attach($notifiable->id);

        }

        //TODO: workspace id for messages
        $msg = Message::create([
                                   'text'     => $data['text'],
                                   'reply_to' => $data['reply_to'],
                                   'user_id'  => $notifUser->id,
                                   'chat_id'  => $chat->id,
                                   'nonce_id' => now()->timestamp,
                               ]);


        sendSocket('messageReceived', $notifiable->socket_id, MessageResource::make($msg));

    }

}
