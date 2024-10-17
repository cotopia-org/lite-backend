<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model {
    use HasFactory;

    public const STATUSES = [
        'in_progress',
        'paused',
        'completed',
    ];

    protected $fillable = [
        'workspace_id',
        'title',
        'description',
        'status',
        'end_at',
        'estimate'
    ];

    protected $casts = [
        'end_at' => 'datetime',
    ];

    public function activities() {
        return $this->hasMany(Activity::class);
    }

    public function getTime($user) {
        $acts = $this->activities()->where('user_id', $user->id);


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


        }
        \Carbon\CarbonInterval::setCascadeFactors([
                                                      'minute' => [60, 'seconds'],
                                                      'hour'   => [60, 'minutes'],
                                                  ]);

        return [
            'job'         => $this,
            'sum_minutes' => $sum_minutes,
            'sum_hours'   => \Carbon\CarbonInterval::minutes($sum_minutes)->cascade()->forHumans(),

        ];
    }

    public function lastActivity() {

        return $this->activities()->whereNull('left_at')->first();

    }

    public function joinUser($user, $role = 'developer') {
        if (!$this->users->contains($user->id)) {
            $this->users()->attach($user, ['role' => $role]);
            //TODO: Socket, user joined to job.

        }

        return $this;

    }

    public function users() {
        return $this->belongsToMany(User::class)->withPivot('role');
    }

    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }
}
