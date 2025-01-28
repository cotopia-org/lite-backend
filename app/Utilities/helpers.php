<?php

use App\Http\Resources\MessageResource;
use App\Jobs\sendSocketJob;
use App\Models\Chat;
use App\Models\Contract;
use App\Models\Message;
use App\Models\User;
use App\Utilities\Constants;
use Carbon\Carbon;
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


function sendToChatGpt($prompt)
{
    $api_key = config('services.openai.api_key');
    if ($api_key === NULL) {
        error('No Api key provided for OpenAi');
    }
    $res = \Illuminate\Support\Facades\Http::
    withOptions(['proxy' => 'http://parham:parham123123@188.165.33.166:48988'])->withHeaders([
                                                                                                 'Authorization' => 'Bearer ' . $api_key
                                                                                             ])
                                           ->post('https://api.openai.com/v1/chat/completions', [
                                               'model'    => 'gpt-4o',
                                               'messages' => [
                                                   [
                                                       'role'        => 'user',
                                                       'content'     => $prompt,
                                                       'temperature' => 0
                                                   ]
                                               ],
                                           ]);
    return $res->json();
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


    sendSocket('messageReceived', $chat->channel, MessageResource::make($msg));

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

function userJoinedToRoomEmit($socket_id, $room_id)
{
    Redis::publish('joined', json_encode([
                                             'socket_id' => $socket_id,
                                             'room_id'   => $room_id
                                         ]));


}

function isNowInUserSchedule($schedule)
{
    $now = now();

    if ($schedule === NULL) {
        return FALSE;
    }
    foreach ($schedule->days as $day) {
        if ((int) $day->day === $now->weekday()) {

            foreach ($day->times as $time) {

                $end = now()->copy()->timezone($schedule->timezone)->setTimeFromTimeString($time->end);
                $start = now()->copy()->timezone($schedule->timezone)->setTimeFromTimeString($time->start);
                if ($now->copy()->timezone($schedule->timezone)->between($start, $end)) {
                    return TRUE;
                }

            }

        }
    }

    return FALSE;


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


function api($data = NULL, $meta = [], $message = Constants::API_SUCCESS_MSG, $code = 1000,
             $http_code = 200): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
{
    if ($message === Constants::API_SUCCESS_MSG) {
        $status = Constants::API_SUCCESS_MSG;
    } else {
        $status = Constants::API_FAILED_MSG;
    }

    $meta['code'] = $code;
    $meta['message'] = $message;


    $response = [
        'status' => $status,
        'meta'   => $meta,
        'data'   => $data,
    ];

    return response($response, $http_code);
}

function api_gateway_error($message = Constants::API_FAILED_MSG)
{
    return api(NULL, [], Constants::API_FAILED_MSG, 0, Response::HTTP_INTERNAL_SERVER_ERROR);
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

function calculateScheduleHours($days)
{
    $hours = 0;
    foreach ($days as $day) {
        foreach ($day['times'] as $time) {
            $end = now()->setTimeFromTimeString($time['end']);
            $start = now()->setTimeFromTimeString($time['start']);


            $hours += $start->diffInHours($end);
        }
    }
    return $hours;
}

function getWeekDays()
{
    return [
        Carbon::SATURDAY  => 0,
        Carbon::SUNDAY    => 1,
        Carbon::MONDAY    => 2,
        Carbon::TUESDAY   => 3,
        Carbon::WEDNESDAY => 4,
        Carbon::THURSDAY  => 5,
        Carbon::FRIDAY    => 6,
    ];
}

function scheduleIsFitInContract($days, $contract)
{
    if ($contract) {

        $contract = Contract::find($contract);


        $hours = calculateScheduleHours($days) * $contract->start_at->weeksInMonth;
        if ($hours > $contract->max_hours) {
            return error('Schedule hours are more than contract max hours');
        }

        if ($hours < $contract->min_hours) {
            return error('Schedule hours are less than contract min hours');
        }
    }

}

function unConvert($value): array|string
{
    $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
    $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];

    return str_replace($western, $eastern, $value);
}


function isActivityInSchedule($schedule, $activity)
{

    $join_at = $activity->join_at;
    $left_at = $activity->left_at ?? now();
    foreach ($schedule->days as $day) {
        if ((int) $day->day === $join_at->weekday()) {

            foreach ($day->times as $time) {

                $end = $join_at->copy()->timezone($schedule->timezone)->setTimeFromTimeString($time->end);
                $start = $join_at->copy()->timezone($schedule->timezone)->setTimeFromTimeString($time->start);
                if ($join_at->copy()->timezone($schedule->timezone)->between($start, $end) && $left_at->copy()
                                                                                                      ->timezone($schedule->timezone)
                                                                                                      ->between($start,
                                                                                                                $end)) {
                    return TRUE;
                }

            }

        }
    }
    return FALSE;


}


