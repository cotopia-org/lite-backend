<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Utilities\Constants;
use App\Utilities\Settingable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

class User extends Authenticatable {
    use HasFactory, Notifiable, HasApiTokens, Settingable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */


    //    protected $with = ['avatar'];
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'active',
        'status',
        'bio',
        'workspace_id',
        'room_id',
        'voice_status',
        'video_status',
        'screenshare_status',
        'coordinates',
        'screenshare_coordinates',
        'screenshare_size',
        'video_coordinates',
        'video_size',
        'is_megaphone',
        'socket_id',
        //        'avatar',
        'is_bot',
        'verified',
        'livekit_connected',
        'active_job_id',
        'active_activity_id',
        'time_started',
        'hard_muted',
        //        'avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }


    public function activeContract() {

        //        $startOfMonth = today()->startOfMonth();
        //        $endOfMonth = today()->endOfMonth();


        $now = now();
        return $this
            ->contracts()->where('start_at', '<=', $now)->where('end_at', '>=', $now)->where('user_sign_status', TRUE)
            ->where('contractor_sign_status', TRUE)->first();
    }

    public function activeJob() {
        return $this
            ->belongsToMany(Job::class)->withPivot('role', 'status')->wherePivot('status', Constants::IN_PROGRESS);
    }

    public static function byUsername($username) {
        return self::where('username', $username)->firstOrFail();

    }

    public function avatar() {
        return $this->morphOne(File::class, 'fileable');
    }

    public function workspaces() {
        return $this->belongsToMany(Workspace::class)->withPivot('role');
    }

    public function tags() {
        return $this->belongsToMany(Tag::class);

    }

    public function isInLk() {
        if ($this->room !== NULL) {
            return $this->room->isUserInLk($this);
        }

        return FALSE;
    }


    public function calculateCommitment($contract = NULL) {
        $user = $this;

        if ($contract === NULL) {
            $contract = $user->activeContract();
        }

        $schedules = $user->scheduleDates($contract);
        if (count($schedules) < 1) {
            return [
                "total_until_now_schedule" => 0,
                "total_schedule"           => 0,
                "done"                     => 0,
                "missing"                  => 0,
                "remaining"                => 0,
                "percentage"               => 0,
                "total_days"               => 0,
                "mustWorkPerDay"           => 0,
                "totalDaysUntilNow"        => 0,
                "minimumWork"              => 0,
            ];
        }

        $totalScheduleDuration = 0;
        $totalUntilNowDuration = 0;
        $totalOverlapDuration = 0;
        $totalDaysUntilNow = 0;
        foreach ($schedules as $date => $schedule) {


            if (!Carbon::parse($date)->gt(now())) {
                $totalDaysUntilNow++;
            }


            foreach ($schedule['times'] as $time) {
                $scheduleStart = $time['start'];
                $scheduleEnd = $time['end'];
                $scheduleDuration = $scheduleStart->diffInMinutes($scheduleEnd);
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

                        //                        $overlapStart = $activityStart;
                        //                        $overlapEnd = $activityEnd;
                        //                        if ($activityStart->lt($scheduleStart)) {
                        //                            $overlapStart = $scheduleStart;
                        //                        }
                        //
                        //                        if ($activityEnd->gt($scheduleEnd)) {
                        //                            $overlapStart = $scheduleEnd;
                        //                        }

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


        $scheduleThreshold = $contract->min_commitment_percent / 100;
        $totalDays = count($schedules);
        $done = $totalOverlapDuration;
        $missing = $totalUntilNowDuration - $done;
        $remaining = $totalScheduleDuration - $totalUntilNowDuration;


        if ($totalDaysUntilNow === 0) {
            $averageWorked = 0;
        } else {
            $averageWorked = $totalOverlapDuration / $totalDaysUntilNow;

        }


        if ($totalDays - $totalDaysUntilNow === 0) {
            $mustWorkPerDay = 0;
        } else {
            $mustWorkPerDay = ((($totalScheduleDuration * $scheduleThreshold) - $totalOverlapDuration) / ($totalDays - $totalDaysUntilNow)) - $averageWorked;

        }


        return [
            "total_until_now_schedule" => $totalUntilNowDuration,
            "total_schedule"           => $totalScheduleDuration,
            "done"                     => $done,
            "missing"                  => $missing,
            "remaining"                => $remaining,
            "percentage"               => round($fulfilledPercentage, 2),
            "total_days"               => $totalDays,
            "mustWorkPerDay"           => $mustWorkPerDay,
            "totalDaysUntilNow"        => $totalDaysUntilNow,
            "minimumWork"              => $totalScheduleDuration * $scheduleThreshold,
            "average"                  => $averageWorked,
            "min_commitment_percent"   => $contract->min_commitment_percent,
        ];
    }

    public function isInSocket() {
        $socket_users = getSocketUsers();
        $socket_user = $socket_users->where('socket_id', $this->socket_id)->first();

        return $socket_user !== NULL;
    }

    public function room() {
        return $this->belongsTo(Room::class);
    }

    public function activities() {
        return $this->hasMany(Activity::class);
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }


    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }

    public function jobs() {
        return $this
            ->belongsToMany(Job::class)->withTimestamps()->withPivot('role', 'status')->wherePivotNotIn('status', [
                Constants::DISMISSED,
            ]);
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        return $this->belongsToMany(Role::class)->withPivot('workspace_id', 'room_id');
    }


    public function isSuperAdmin($workspace) {
        return $this->roles->where('title', 'super-admin')->where('workspace_id', $workspace->id)->first() !== NULL;
    }


    public function checkIsInRoomForReal() {


        if ($this->room_id === NULL) {

        }

    }

    public function giveRole($role, $workspace_id, $attach = TRUE) {


        if (!$role instanceof Role) {
            $role = Role::where('title', $role)->firstOrFail();

        }
        $permissions = $role->permissions;
        $currentToken = $this->currentAccessToken();
        //        $abilities = $currentToken->abilities;
        $abilities = [];

        foreach ($permissions as $permission) {
            $abilities[] = $permission->title . '-' . $workspace_id;

        }
        $currentToken->abilities = $abilities;
        $currentToken->save();


        if ($attach) {
            $this->roles()->attach($role, [
                'workspace_id' => $workspace_id,
                //                'room_id'      => $room?->id,
            ]);
        }
    }


    public function mentions() {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    public function mentionedBy() {
        return $this->username;
    }

    public function isOwner($id): bool {
        return (int)$this->id === (int)$id;
    }

    public function reports() {
        return $this->hasMany(Report::class);
    }

    public function createToken(string $name, $abilities = [], $expiresAt = NULL): NewAccessToken {
        $plainTextToken = $this->generateTokenString();

        $abs = $this->getAbilities();
        $token = $this->tokens()->create([
                                             'name'       => $name,
                                             'token'      => hash('sha256', $plainTextToken),
                                             'abilities'  => $abs,
                                             'expires_at' => $expiresAt,
                                         ]);

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }


    public function payments() {
        return $this->hasMany(Payment::class);
    }

    public function contracts() {
        return $this->hasMany(Contract::class);
    }

    public function channels() {
        $workspaces = $this->workspaces->pluck('channel');
        $chats = $this->chats->pluck('channel');

        $arr = $workspaces->merge($chats);
        if ($this->room !== NULL) {
            $room = $this->room->channel;
            $arr->merge($room);
        }


        return $arr->values()->toArray();
    }


    public function real_chats($workspaces = NULL, $workspace_id = NULL) {

        $chats = $this->chats()->with('messages', 'users')->get();

        if ($workspaces === NULL && $workspace_id === NULL) {
            $workspaces = $this->workspaces()->with('chats', 'chats.messages', 'chats.users', 'chats.workspace')->get();

        }
        if ($workspace_id !== NULL) {

            $chats = $chats->merge($this
                                       ->workspaces()->with('chats', 'chats.messages', 'chats.users', 'chats.workspace')
                                       ->findOrFail($workspace_id)->chats);
        } else {

            foreach ($workspaces as $workspace) {
                $chats = $chats->merge($workspace->chats);

            }


        }

        return $chats;
    }


    public function folders() {
        return $this->hasMany(Folder::class);
    }

    public function chats() {
        return $this
            ->belongsToMany(Chat::class)->withTimestamps()
            ->withPivot('role', 'last_message_seen_id', 'muted', 'folder_id');
    }

    public function updateActiveJob($job_id = NULL) {

        $this->update(['active_job_id' => $job_id]);
        //        $this->refreshActivity();
    }

    public function refreshActivity() {
        $room = $this->room;


        $this->left('Left Refreshed By RefreshActivity in User');

        $this->joined($room, 'Refreshed By RefreshActivity in User');


    }


    public function joined($room, $data) {
        if ($this->active_activity_id === NULL || $this->active_activity_id === 0) {

            $act = $this->activities()->create([
                                                   'join_at'      => now(),
                                                   'left_at'      => NULL,
                                                   'workspace_id' => $room->workspace->id,
                                                   'room_id'      => $room->id,
                                                   'job_id'       => $this->active_job_id,
                                                   'data'         => $data,
                                               ]);
            $this->update([
                              'active_activity_id' => $act->id,
                          ]);

            return TRUE;
        }
        return FALSE;

    }


    public function lastActivity() {
        return $this->belongsTo(Activity::class, 'active_activity_id');


    }

    public function left($data = NULL) {

        $last_activity = $this->lastActivity;
        if ($last_activity !== NULL) {
            $last_activity->update([
                                       'left_at' => now(),
                                       'data'    => $data,

                                   ]);
            $this->update([
                              'active_activity_id' => NULL
                          ]);
            return TRUE;
        }
        return FALSE;

    }


    public function getTimeWithSchedule($contract) {
        $user = $this;
        if ($contract === NULL) {
            return [
                'sum_minutes'     => 0,
                'idle_minutes'    => 0,
                'working_minutes' => 0,
                'sum_hours'       => 0,

            ];
        }
        //        $acts = Activity::where('user_id', $user->id)
        //                        ->where('workspace_id', $contract->workspace_id)
        //                        ->where('created_at', '>=', $contract->start_at)->where('created_at', '<=', $contract->end_at)
        //                        ->get();
        //        $diffs = 0;
        //        $dates = $user->scheduleDates();
        //        foreach ($acts as $act) {
        //
        //            $diffs += activityDiffWithSchedule($dates, $act);
        //
        //
        //        }
        $time = $this->calculateCommitment($contract);

        \Carbon\CarbonInterval::setCascadeFactors([
                                                      'minute' => [60, 'seconds'],
                                                      'hour'   => [60, 'minutes'],
                                                  ]);
        return [
            'sum_minutes'     => $time['done'],
            'idle_minutes'    => 0,
            'working_minutes' => 0,
            'sum_hours'       => \Carbon\CarbonInterval::minutes($time['done'])->cascade()->forHumans(),

        ];
    }

    public function getTime($startAt = NULL, $endAt = NULL, $workspace_id = NULL) {


        $query = DB::table('activities')->where('user_id', $this->id)
                   ->select('user_id', DB::raw('SUM(TIMESTAMPDIFF(SECOND, join_at, IFNULL(left_at, NOW())) / 60) as sum_minutes'), DB::raw('SUM(IF(job_id IS NULL, TIMESTAMPDIFF(SECOND, join_at, IFNULL(left_at, NOW())) / 60, 0)) as idle'), DB::raw('SUM(IF(job_id IS NOT NULL, TIMESTAMPDIFF(SECOND, join_at, IFNULL(left_at, NOW())) / 60, 0)) as working'));


        if ($startAt !== NULL) {
            $query->where('created_at', '>=', $startAt);
        }
        if ($endAt !== NULL) {
            $query->where('created_at', '<=', $endAt);
        }
        if ($workspace_id !== NULL) {
            $query->where('workspace_id', $workspace_id);
        }

        $act = $query
            ->groupBy('user_id') // Ensure this matches the selected non-aggregate column
            ->first();


        \Carbon\CarbonInterval::setCascadeFactors([
                                                      'minute' => [60, 'seconds'],
                                                      'hour'   => [60, 'minutes'],
                                                  ]);
        return [
            'sum_minutes'     => (float)$act?->sum_minutes,
            'idle_minutes'    => (float)$act?->idle,
            'working_minutes' => (float)$act?->working,
            'sum_hours'       => \Carbon\CarbonInterval::minutes($act?->sum_minutes)->cascade()->forHumans(),

        ];
    }

    public function scheduleDates($contract) {
        $activeContract = $contract;
        if ($contract === NULL) {
            $activeContract = $this->activeContract();

        }


        if ($activeContract === NULL || $activeContract->schedule === NULL) {
            return [];
        }

        $days = collect($activeContract->schedule->days);
        $schedule = $activeContract->schedule;


        $firstDate = $activeContract->start_at;
        $maxDays = $activeContract->start_at->diffInDays($activeContract->end_at);


        $dates = [];
        for ($i = 0; $i < $maxDays; $i++) {

            $day = $firstDate->copy()->addDays($i);


            $dayInSchedule = $days->where('day', $day->dayOfWeek)->first();
            if ($dayInSchedule !== NULL) {
                $dates[$day->toDateString()] = [
                    'date' => $day->toDateString(),
                ];
                foreach ($dayInSchedule->times as $time) {
                    $dates[$day->toDateString()]['times'][] = [
                        'start' => $day
                            ->copy()->timezone($schedule->timezone)->setTimeFromTimeString($time->start)
                            ->timezone('UTC'),
                        'end'   => $day
                            ->copy()->timezone($schedule->timezone)->setTimeFromTimeString($time->end)->timezone('UTC'),
                    ];

                }


            }


        }
        return $dates;
    }

    public function getScheduledHoursInWeek() {
        \Carbon\CarbonInterval::setCascadeFactors([
                                                      'minute' => [60, 'seconds'],
                                                      'hour'   => [60, 'minutes'],
                                                  ]);
        $minutes = 0;
        foreach ($this->schedules as $schedule) {
            foreach ($schedule->days as $day) {
                foreach ($day->times as $time) {
                    $end = now()->setTimeFromTimeString($time->end);
                    $start = now()->setTimeFromTimeString($time->start);


                    $minutes += $start->diffInMinutes($end);
                }
            }
        }
        return [
            'minutes' => $minutes,
            'hours'   => \Carbon\CarbonInterval::minutes($minutes)->cascade()->forHumans(),
        ];
    }

    public function schedules() {
        return $this->hasMany(Schedule::class);
    }

    public function talks() {
        return $this->hasMany(Talk::class);
    }


    public function canDo($ability, $workspace_id) {
        $user_in_workspace = $this->workspaces->find($workspace_id);
        if ($user_in_workspace === NULL) {
            return error('You dont have permission to do this action.');
        }
        $role = $user_in_workspace->pivot->role;

        if ($role === 'super-admin' || $role === 'owner') {
            return TRUE;
        }
        $permissions = Constants::ROLE_PERMISSIONS[$role];

        if (!in_array($ability, $permissions, TRUE)) {
            return error('You dont have permission to do this action.');

        }

    }

    public function isAFK() {
        return $this->status === Constants::AFK;

    }

    public function isGhost() {
        return $this->status === Constants::GHOST;

    }

    public function isOnline() {
        return $this->status === Constants::ONLINE;
    }


    public function availabilities() {
        return $this->hasMany(Availability::class);
    }

    public function getAbilities(): array {

        $abilities = [];

        foreach ($this->roles as $role) {
            $permissions = $role->permissions;

            foreach ($permissions as $permission) {
                $abilities[] = $permission->title . '-' . $role->pivot->workspace_id;

            }
        }
        return $abilities;

    }

    public function timeStarted() {
        $user = $this;


        $activeContract = $user->activeContract();

        if ($activeContract === NULL) {
            return FALSE;
        }
        if ($activeContract->in_schedule && !isNowInUserSchedule($activeContract->schedule)) {
            return FALSE;

        }
        if ($activeContract->in_job && $user->active_job_id === NULL) {
            return FALSE;

        }

        return TRUE;

    }
}
