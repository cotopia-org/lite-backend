<?php

use App\Http\Resources\MessageResource;
use App\Jobs\sendSocketJob;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Utilities\Constants;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;


function sendSocket($eventName, $channel, $data)
{
    if ($channel !== NULL) {
        sendSocketJob::dispatch([
                                    'eventName' => $eventName,
                                    'channel'   => $channel,
                                    'data'      => $data,
                                ]);
    }


}

function updateMesssage($message, $text, $reply_to = NULL)
{

    $message->update([
                         'text'      => $text,
                         'is_edited' => TRUE,
                         'reply_to'  => $reply_to,

                     ]);
}

function sendMessage($message, $chat_id, $reply_to = NULL)
{
    //TODO: has to change to notification or Job.
    $notifUser = User::find(41);
    $chat = Chat::find($chat_id);


    if ($chat->users()->find($notifUser) === NULL) {
        $chat->users()->attach($notifUser->id);

    }


    $msg = Message::create([
                               'text'     => $message,
                               'reply_to' => $reply_to,
                               'user_id'  => $notifUser->id,
                               'chat_id'  => $chat->id,
                               'nonce_id' => random_int(100000, 999999),
                           ]);


    sendSocket('messageReceived', $chat->workspace->channel, MessageResource::make($msg));

    return $msg;

}

function getSocketUsers()
{
    try {
        return collect(\Http::get(get_socket_url('sockets'))->json());
    } catch (\Exception $e) {
        return collect([]);
    }
}

function userJoinedToRoomEmit($user_id, $room_id)
{
    Redis::publish('joined', json_encode([
                                             'user_id' => $user_id,
                                             'room_id' => $room_id
                                         ]));


}

function acted($user_id, $workspace_id, $room_id, $job_id, $type, $description)
{


    return \App\Models\Act::create([
                                       'user_id'      => $user_id,
                                       'workspace_id' => $workspace_id,
                                       'room_id'      => $room_id,
                                       'job_id'       => $job_id,
                                       'type'         => $type,
                                       'description'  => $description,
                                   ]);

}

function get_enum_values($cases, $key = FALSE): array
{
    return array_column($cases, 'value', $key ? 'name' : NULL);
}


function api($data = NULL, $message = Constants::API_SUCCESS_MSG, $code = 1000,
             $http_code = 200): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
{
    if ($message === Constants::API_SUCCESS_MSG) {
        $status = Constants::API_SUCCESS_MSG;
    } else {
        $status = Constants::API_FAILED_MSG;
    }
    $response = [
        'status' => $status,
        'meta'   => [
            // TODO - Websocket code is not required here!
            'code'    => $code,
            'message' => $message,
        ],
        'data'   => $data,
    ];

    return response($response, $http_code);
}

function api_gateway_error($message = Constants::API_FAILED_MSG)
{
    return api(NULL, Constants::API_FAILED_MSG, 0, Response::HTTP_INTERNAL_SERVER_ERROR);
}

/**
 * @throws Exception
 */
function error($message, $code = 400)
{

    throw new RuntimeException($message, $code);
    //    throw new HttpException($code, $message, NULL, [], $code);

}

function convert($value): array|string
{
    $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
    $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];

    return str_replace($eastern, $western, $value);
}


function get_socket_url($path = ""): string
{
    return rtrim(config('socket.base_url'), '/') . '/' . $path;
}

function unConvert($value): array|string
{
    $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
    $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];

    return str_replace($western, $eastern, $value);
}
