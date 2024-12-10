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
use App\Http\Resources\UserMinimalResource;
use App\Http\Resources\UserResource;
use App\Models\File;
use App\Models\Job;
use App\Models\Mention;
use App\Models\Room;
use App\Models\Tag;
use App\Models\User;
use App\Models\Workspace;
use App\Utilities\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller {
    public function me() {
        return api(UserResource::make(auth()->user()));
    }

    public function settings() {
        return SettingResource::collection(auth()->user()->settings);
    }

    public function mentionedJobs() {
        $user = auth()->user();


        $tags = $user->tags();
        $user_mentions = $user->mentions()->whereNotNull('job_id')->get();
        $tag_mentions = Mention::whereNotNull('job_id')->where('mentionable_type', Tag::class)
                               ->whereIn('mentionable_id', $tags->get()->pluck('id'))->get();

        $mentions = $user_mentions->merge($tag_mentions);

        return api(JobResource::collection(Job::whereNot('user_id', $user->id)
                                              ->whereIn('id', $mentions->pluck('job_id'))->get()));

    }

    public function jobs(Request $request, $user) {
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


        return api(JobResource::collection($jobs->get()));
    }

    public function scheduleFulfillment(Request $request, $user) {
        if ($user === "me") {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }

        $firstOfMonth = today()->firstOfMonth();

        //
        $activities = $user
            ->activities()->where("created_at", ">=", $firstOfMonth)->get();

        $schedules = $user->thisWeekSchedules();

        $sum_minutes = 0;
        $schedule_total = $user->getScheduledHoursInWeek();
        foreach ($schedules as $schedule) {
            $acts = $activities
                ->where("left_at", ">=", $schedule["start"])->where("join_at", "<=", $schedule["end"]);

            if (count($acts) > 0) {
                $left_at = now();

                foreach ($acts as $act) {
                    if ($act->left_at !== NULL) {
                        $left_at = $act->left_at;
                    }

                    $diff = $act->join_at->diffInMinutes($left_at);
                    $sum_minutes += $diff;
                }
            }
        }
        return api([
                       "total_week_schedules"               => $schedule_total["minutes"],
                       "total_week_activities_in_schedules" => $sum_minutes,
                       "percentage"                         => ($sum_minutes / $schedule_total["minutes"]) * 100,
                   ]);
    }

    public function tags(Request $request, $user) {
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

    public function search(Request $request) {
        //TODO: have to use meiliserach instead
        $search = $request->search;
        $users = User::where(function ($query) use ($search) {
            $query
                ->where("name", "LIKE", $search . "%")->orWhere("username", "LIKE", $search . "%")
                ->orWhere("email", "LIKE", $search . "%");
        })->get();
        return api(UserMinimalResource::collection($users));
    }

    public function updateCoordinates(Request $request) {
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

    public function toggleMegaphone() {
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

    public function unGhost() {
        $user = auth()->user();
        $user->update([
                          "status" => Constants::ONLINE,
                      ]);
        $response = UserMinimalResource::make($user);

        sendSocket(Constants::userUpdated, $user->room->channel, $response);
        return api($response);
    }

    public function update(Request $request) {
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

    public function activities(Request $request) {
        $user = auth()->user();


        return api($user->getTime(today(), today()->addDay(), $user->workspace_id)["sum_minutes"]);
    }

    public function chats(Request $request) {
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
                                                                                                           ->where("messages.id", ">", DB::raw("chat_user.last_message_seen_id"))
                                                                                                           ->where("messages.created_at", ">=", DB::raw("chat_user.created_at"));
                                                                                                   },
                                                                                                   "mentions" => function ($query) use ($user) {
                                                                                                       $query
                                                                                                           ->where("mentions.message_id", ">", DB::raw("chat_user.last_message_seen_id"))
                                                                                                           ->where("mentions.mentionable_type", User::class)
                                                                                                           ->where("mentions.mentionable_id", $user->id);
                                                                                                   },
                                                                                               ])->get()
                                                ->sortByDesc('lastMessage.created_at')));
    }

    public function talks() {
        return api(TalkResource::collection(auth()->user()->talks));
    }

    public function schedules($user) {
        if ($user === "me") {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }
        //For commit test.

        return api(ScheduleResource::collection($user->schedules));
    }


    public function payments($user) {

        if ($user === "me") {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }


        return api(PaymentResource::collection($user->payments));


    }


    public function contracts($user) {

        if ($user === "me") {
            $user = auth()->user();
        } else {
            $user = User::findOrFail($user);
        }


        return api(ContractResource::collection($user->contracts));


    }
}
