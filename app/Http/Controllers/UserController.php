<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Http\Resources\ChatResource;
use App\Http\Resources\ContractResource;
use App\Http\Resources\JobResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\RoomResource;
use App\Http\Resources\ScheduleResource;
use App\Http\Resources\SettingResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TalkResource;
use App\Http\Resources\UserJobResource;
use App\Http\Resources\UserMinimalResource;
use App\Http\Resources\UserResource;
use App\Jobs\DisconnectUserJob;
use App\Models\Activity;
use App\Models\File;
use App\Models\Job;
use App\Models\Mention;
use App\Models\Room;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use App\Utilities\Constants;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function me()
    {
        return api(UserResource::make(auth()->user()));
    }

    public function settings()
    {
        return SettingResource::collection(auth()->user()->settings);
    }

    public function mentionedJobs(Request $request)
    {
        $user = auth()->user();


        $tags = $user->tags();
        $user_mentions = $user->mentions()->whereNotNull('job_id')->get();
        $tag_mentions = Mention::whereNotNull('job_id')->where('mentionable_type', Tag::class)
                               ->whereIn('mentionable_id', $tags->get()->pluck('id'))->get();

        $mentions = $user_mentions->merge($tag_mentions);
        $mentions_ids = $mentions->pluck('job_id');


        $final_jobs = $mentions_ids;

        if ($request->suggests) {
            $user_jobs_ids = DB::table('job_user')->where('user_id', $user->id)->pluck('job_id');
            $final_jobs = $mentions_ids->diff($user_jobs_ids);

        }


        return api(JobResource::collection(Job::find($final_jobs)));

    }

    public function jobs(Request $request, $user)
    {
        $firstOfMonth = now()->firstOfMonth();


        $period = $request->period ?? 'all_time';
        if ($user === "me") {
            $user = auth()->user();


        } else {
            $user = User::findOrFail($user);
        }
        $jobs = $user->jobs()->orderBy("updated_at", "DESC");


        if ($request->workspace_id) {
            $jobs = $jobs->where("workspace_id", $request->workspace_id);
        }

        if ($period === 'this_month') {
            $jobs = $jobs->whereHas('acts', function ($query) use ($firstOfMonth) {
                $query->where('created_at', '>=', $firstOfMonth);
            });
        }


        return api(UserJobResource::collection($jobs->get()));
    }

    public function scheduleCommitment(Request $request, $user)
    {
        if ($user === "me") {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }

        //        $firstOfMonth = today()->firstOfMonth();

        $schedules = $user->scheduleDates();

        $totalScheduleDuration = 0;
        $totalUntilNowDuration = 0;
        $totalOverlapDuration = 0;
        $totalDayWorked = 0;
        $data = [];
        foreach ($schedules as $date => $schedule) {


            if (!Carbon::parse($date)->gt(now())) {
                $totalDayWorked++;
            }


            foreach ($schedule['times'] as $time) {
                $scheduleStart = $time['start'];
                $scheduleEnd = $time['end'];
                $scheduleDuration = $scheduleStart->diffInMinutes($scheduleEnd);
                $data[] = $time;
                $totalScheduleDuration += $scheduleDuration;

                if (!Carbon::parse($date)->gt(now())) {
                    $totalUntilNowDuration += $scheduleDuration;

                    $overlappingActivities = Activity::where('user_id', $user->id)
                                                     ->where(function ($query) use ($scheduleStart, $scheduleEnd) {
                                                         $query
                                                             ->whereBetween('join_at', [$scheduleStart, $scheduleEnd])
                                                             ->orWhereBetween('left_at', [$scheduleStart, $scheduleEnd])
                                                             ->orWhere(function ($subQuery) use (
                                                                 $scheduleStart, $scheduleEnd
                                                             ) {
                                                                 $subQuery
                                                                     ->where('join_at', '<=', $scheduleStart)
                                                                     ->where('left_at', '>=', $scheduleEnd);
                                                             });
                                                     })->get();


                    foreach ($overlappingActivities as $activity) {
                        $activityStart = $activity->join_at;
                        $activityEnd = $activity->left_at;


                        $overlapStart = max($scheduleStart, $activityStart);
                        $overlapEnd = min($scheduleEnd, $activityEnd);


                        if ($overlapStart < $overlapEnd) {
                            $totalOverlapDuration += $overlapStart->diffInMinutes($overlapEnd);
                        }
                    }

                }


            }


        }

        if ($totalUntilNowDuration === 0) {
            $fulfilledPercentage = 0;
        } else {
            $fulfilledPercentage = ($totalOverlapDuration / $totalUntilNowDuration) * 100;

        }


        $scheduleThreshold = 80;
        $totalDays = count($schedules);
        $done = $totalOverlapDuration;
        $missing = $totalUntilNowDuration - $done;
        $remaining = $totalScheduleDuration - $totalUntilNowDuration;
        $mustWorkPerDay = ((($totalScheduleDuration - $done) / $totalDays - $totalDayWorked) * $scheduleThreshold) - $totalScheduleDuration / $totalDays;


        return api([
                       "total_until_now_schedule" => $totalUntilNowDuration,
                       "total_schedule"           => $totalScheduleDuration,
                       "done"                     => $done,
                       "missing"                  => $missing,
                       "remaining"                => $remaining,
                       "percentage"               => round($fulfilledPercentage, 2),
                       "data"                     => $data,
                       "total_days"               => $totalDays,
                       "mustWorkPerDay"           => $mustWorkPerDay,
                       "totalDayWorked"           => $totalDayWorked,
                   ]);
    }

    public function tags(Request $request, $user)
    {
        if ($user === "me") {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }
        $tags = $user->tags();
        if ($request->workspace_id) {
            $tags = $tags
                ->orderBy("id", "DESC")->where("workspace_id", $request->workspace_id);
        }
        return api(TagResource::collection($tags->get()));
    }

    public function search(Request $request)
    {
        //TODO: have to use meiliserach instead
        $search = $request->search;
        $users = User::where(function ($query) use ($search) {
            $query
                ->where("name", "LIKE", $search . "%")->orWhere("username", "LIKE", $search . "%")
                ->orWhere("email", "LIKE", $search . "%");
        })->get();
        return api(UserMinimalResource::collection($users));
    }

    public function updateCoordinates(Request $request)
    {
        $user = auth()->user();
        $request->validate([
                               "coordinates" => "required",
                           ]);

        $user->update([
                          "coordinates" => $request->coordinates,
                      ]);

        $response = UserMinimalResource::make($user);

        if ($user->room !== NULL) {
            sendSocket(Constants::userUpdated, $user->room->channel, $response);
        }
        return api($response);
    }

    public function toggleMegaphone()
    {
        $user = auth()->user();

        $user->update([
                          "is_megaphone" => !$user->is_megaphone,
                      ]);

        $response = UserMinimalResource::make($user);
        if ($user->room !== NULL) {
            sendSocket(Constants::userUpdated, $user->room->channel, $response);
        }

        return api($response);
    }

    public function unGhost()
    {
        $user = auth()->user();
        $user->update([
                          "status" => Constants::ONLINE,
                      ]);
        $response = UserMinimalResource::make($user);

        sendSocket(Constants::userUpdated, $user->room->channel, $response);
        return api($response);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $user->update([
                          "name"               => $request->name ?? $user->name,
                          "voice_status"       => $request->voice_status ?? $user->voice_status,
                          "video_status"       => $request->video_status ?? $user->video_status,
                          "screenshare_status" => $request->screenshare_status ?? $user->screenshare_status,
                          "livekit_connected"  => $request->livekit_connected ?? $user->livekit_connected,
                      ]);

        File::syncFile($request->avatar_id, $user, "avatar");
        $response = UserMinimalResource::make($user);

        sendSocket(Constants::userUpdated, $user->workspace?->channel, $response);

        return api($response);
    }

    public function activities(Request $request)
    {
        $user = auth()->user();
        $time_start = TRUE;


        if ($user->activeContract() !== NULL) {
            if ($user->activeContract()->in_schedule && !isNowInUserSchedule($user->activeContract()->schedule)) {
                $time_start = FALSE;

            }
        }

        if ($request->new) {

            return api([
                           'minutes'    => $user->getTime(today(), today()->addDay(),
                                                          $user->workspace_id)["sum_minutes"],
                           'time_count' => $time_start
                       ]);
        }


        return api($user->getTime(today(), today()->addDay(), $user->workspace_id)["sum_minutes"]);

    }

    public function chats(Request $request)
    {
        $user = auth()->user();

        return api(ChatResource::collection($user
                                                ->chats()->has('messages')->with([
                                                                                     //                                                                     'messages'    => ['files', 'mentions', 'links'],
                                                                                     "lastMessage" => [
                                                                                         "files",
                                                                                         "mentions",
                                                                                         "links"
                                                                                     ],
                                                                                     "users"       => ["avatar"],
                                                                                     "mentions",
                                                                                 ])->withCount([
                                                                                                   "messages" => function ($query) {
                                                                                                       $query
                                                                                                           ->where("messages.id",
                                                                                                                   ">",
                                                                                                                   DB::raw("chat_user.last_message_seen_id"))
                                                                                                           ->where("messages.created_at",
                                                                                                                   ">=",
                                                                                                                   DB::raw("chat_user.created_at"));
                                                                                                   },
                                                                                                   "mentions" => function ($query) use
                                                                                                   (
                                                                                                       $user
                                                                                                   ) {
                                                                                                       $query
                                                                                                           ->where("mentions.message_id",
                                                                                                                   ">",
                                                                                                                   DB::raw("chat_user.last_message_seen_id"))
                                                                                                           ->where("mentions.mentionable_type",
                                                                                                                   User::class)
                                                                                                           ->where("mentions.mentionable_id",
                                                                                                                   $user->id);
                                                                                                   },
                                                                                               ])->get()
                                                ->sortByDesc('lastMessage.created_at')));
    }

    public function talks()
    {
        return api(TalkResource::collection(auth()->user()->talks));
    }

    public function schedules($user)
    {
        if ($user === "me") {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }


        $contract = $user->activeContract();


        return api(ScheduleResource::collection($user->schedules->where('contract_id', $contract?->id)));
    }


    public function payments($user)
    {

        if ($user === "me") {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }


        return api(PaymentResource::collection($user->payments));


    }


    public function contracts($user)
    {

        if ($user === "me") {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }


        return api(ContractResource::collection($user->contracts()->orderBy('id', 'DESC')->get()));


    }


    public function beAfk()
    {
        $user = auth()->user();
        $user->update([
                          "status" => Constants::AFK,
                      ]);
        $response = UserMinimalResource::make($user);


        if ($user->room_id !== NULL) {
            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'time_ended',
                  'UserController@afk');
        }

        acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'disconnected',
              'UserController@afk');

        if ($user->active_job_id !== NULL) {
            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended',
                  'UserController@afk');

        }
        $user->left('Disconnected for Afk in UserController@afk');


        sendSocket(Constants::userUpdated, $user->workspace->channel, $response);
        return api($response);


    }


    public function beOnline()
    {
        $user = auth()->user();

        $user->update([
                          "status" => Constants::ONLINE,
                      ]);
        $response = UserMinimalResource::make($user);

        $room = $user->room;

        acted($user->id, $room->workspace_id, $room->id, $user->active_job_id, 'time_started',
              'UserController@beOnline');
        if ($user->active_job_id !== NULL) {
            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_started',
                  'UserController@beOnline');

        }
        $user->joined($room, 'Connected From UserController beOnline Method');


        sendSocket(Constants::userUpdated, $user->room->channel, $response);
        return api($response);
    }


}
