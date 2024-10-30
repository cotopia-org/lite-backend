<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Utilities\Constants;
use App\Utilities\Settingable;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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


    protected $with = ['avatar'];
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
        'avatar',
        'is_bot',
        'verified',
        'livekit_connected',
        'active_job_id'
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


    public static function byUsername($username) {
        return self::where('username', $username)->firstOrFail();

    }

    public function avatar() {
        return $this->morphOne(File::class, 'fileable');
    }

    public function workspaces() {
        return $this->belongsToMany(Workspace::class)->withPivot('role', 'tag_id');
    }

    public function isInLk() {
        if ($this->room !== NULL) {
            return $this->room->isUserInLk($this);
        }

        return FALSE;
    }


    public function isInSocket() {

        $socket_users = collect(\Http::get('http://localhost:3010/sockets')->json());
        $socket_user = $socket_users->where('username', $this->username)->first();
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
        return $this->belongsToMany(Job::class)->withPivot('role');
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

        $abilities = $this->getAbilities();
        $token = $this->tokens()->create([
                                             'name'       => $name,
                                             'token'      => hash('sha256', $plainTextToken),
                                             'abilities'  => $abilities,
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
        $workspaces = $this->workspaces;
        $chats = $this->real_chats($workspaces, NULL)->pluck('channel');
        $workspaces = $workspaces->pluck('channel');

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

    public function chats() {
        return $this->belongsToMany(Chat::class);
    }

    public function updateActiveJob($job_id = NULL) {

        $this->update(['active_job_id' => $job_id]);
        $this->refreshActivity();
    }

    public function refreshActivity() {
        $user = $this;
        $room = $user->room;


        $user->left('Left Refreshed By RefreshActivity in User');
        $user->activities()->create([
                                        'join_at'      => now(),
                                        'left_at'      => NULL,
                                        'workspace_id' => $room->workspace->id,
                                        'room_id'      => $room->id,
                                        'job_id'       => $user->active_job_id,
                                        'data'         => 'Refreshed By RefreshActivity in User',
                                    ]);


    }

    public function lastActivity() {
        return $this->activities()->whereNull('left_at')->first();


    }

    public function left($data = NULL) {

        $last_activity = $this->lastActivity();
        if ($last_activity !== NULL) {
            $last_activity->update([
                                       'left_at' => now(),
                                       'data'    => $data,

                                   ]);
        }

    }


    public function getTime($period = NULL, $startAt = NULL, $endAt = NULL, bool|null $expanded = TRUE, $workspace = NULL) {

        $acts = $this->activities();

        $today = today();
        $now = now();

        if ($workspace !== NULL) {
            $acts->where('workspace_id', $workspace);
        }
        if ($period === 'today') {

            $acts = $acts->where('created_at', '>=', today());


        }


        if ($period === 'yesterday') {

            $acts = $acts->where('created_at', '>=', today()->subDay())->where('created_at', '<=', today());


        }

        if ($period === 'currentMonth') {

            $acts = $acts->where('created_at', '>=', now()->firstOfMonth());


        }
        if ($startAt !== NULL) {
            $acts = $acts->where('created_at', '>=', Carbon::createFromDate($startAt));

        }

        if ($endAt !== NULL) {
            $acts = $acts->where('created_at', '<', Carbon::createFromDate($endAt));

        }

        $sum_minutes = 0;
        $data = [];
        $acts = $acts->get();
        foreach ($acts as $act) {


            $left_at = now();
            if ($act->left_at !== NULL) {
                $left_at = $act->left_at;
            }

            $diff = $act->join_at->diffInMinutes($left_at);
            $sum_minutes += $diff;
            $data[] = 'Joined: ' . $act->join_at
                    ->timezone('Asia/Tehran')->toDateTimeString() . ' Left: ' . $left_at
                          ->timezone('Asia/Tehran')->toDateTimeString() . ' Diff: ' . $diff;

        }
        \Carbon\CarbonInterval::setCascadeFactors([
                                                      'minute' => [60, 'seconds'],
                                                      'hour'   => [60, 'minutes'],
                                                  ]);

        return [
            'user'        => $this->username,
            //            'count'       => $acts->count(),
            'sum_minutes' => $sum_minutes,
            'sum_hours'   => \Carbon\CarbonInterval::minutes($sum_minutes)->cascade()->forHumans(),
            //            'data'        => $expanded ? $data : [],
            //            'activities'  => $expanded ? $acts->map(function ($act) {
            //                return [
            //                    'id'         => $act->id,
            //                    'join_at'    => $act->join_at->timezone('Asia/Tehran')->toDayDateTimeString(),
            //                    'left_at'    => $act->left_at?->timezone('Asia/Tehran')->toDayDateTimeString(),
            //                    'created_at' => $act->created_at->timezone('Asia/Tehran')->toDayDateTimeString(),
            //                ];
            //            }) : [],
        ];

    }

    public function thisWeekSchedules() {

        $schedules = $this->schedules;
        $today = today();
        $weekDays = [
            Carbon::SATURDAY  => 0,
            Carbon::SUNDAY    => 1,
            Carbon::MONDAY    => 2,
            Carbon::TUESDAY   => 3,
            Carbon::WEDNESDAY => 4,
            Carbon::THURSDAY  => 5,
            Carbon::FRIDAY    => 6,
        ];

        $todayWeekDay = $weekDays[$today->dayOfWeek];
        $weekDates = [];

        for ($i = 0; $i <= 6; $i++) {
            if ($i === $todayWeekDay) {
                $weekDates[$i] = [
                    'date'      => $today,
                    'scheduled' => FALSE

                ];
            }
            if ($i > $todayWeekDay) {
                $weekDates[$i] = [
                    'date'      => $today->copy()->addDays($i - $todayWeekDay),
                    'scheduled' => FALSE

                ];
            }

            if ($i < $todayWeekDay) {
                $weekDates[$i] = [
                    'date'      => $today->copy()->subDays($todayWeekDay - $i),
                    'scheduled' => FALSE,


                ];
            }


        }

        $data = [];
        foreach ($schedules as $schedule) {
            foreach ($schedule->days as $day) {
                foreach ($day->times as $time) {

                    $date = $weekDates[$day->day]['date'];
                    $data[] = [
                        'start' => $date->copy()->setTimeFromTimeString($time->start),
                        'end'   => $date->copy()->setTimeFromTimeString($time->end),
                    ];

                }


            }
        }
        return $data;
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
            return error('You cant do this.');
        }
        $role = $user_in_workspace->pivot->role;

        if ($role === 'super-admin') {
            return TRUE;
        }
        $permissions = Constants::ROLE_PERMISSIONS[$role];

        if (!in_array($ability, $permissions, TRUE)) {
            return error('You cant do this.');

        }

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
}
