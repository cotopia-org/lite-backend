<?php

namespace App\Notifications;

use App\Notifications\Channels\MessengerChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class newNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */


    public function __construct(private readonly string $text, private readonly ?int $reply_to = NULL,
                                private readonly ?int   $chat_id = NULL)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [MessengerChannel::class];
    }

    public function toMessenger(object $notifiable): array
    {
        return [
            'text'     => $this->text,
            'reply_to' => $this->reply_to,
            'chat_id'  => $this->chat_id,

        ];
    }


}
