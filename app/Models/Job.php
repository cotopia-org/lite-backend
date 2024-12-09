<?php

namespace App\Models;

use App\Utilities\Constants;
use Carbon\Carbon;
use Carbon\CarbonInterval;
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
        'estimate',
        'start_at',
        'duration',
        'message_id',
        'job_id',
        'level',
        'old',
    ];

    protected $casts = [
        'end_at'   => 'datetime',
        'start_at' => 'datetime',
    ];

    protected static function booted(): void {


        static::updated(function (Job $job) {
            $user = $job->users->first();
            $status = NULL;
            if ($job->status === Constants::IN_PROGRESS) {
                $status = 'In Progress 🔵';
            }
            if ($job->status === Constants::PAUSED) {
                $status = 'Paused 🟡';
            }
            if ($job->status === Constants::COMPLETED) {
                $status = 'Completed 🟢';
            }


            $text = $this->getMessageText($user);


            updateMesssage(Message::find($job->message_id), $text);


        });
    }


    public function getMessageText($user) {
        $nl = PHP_EOL;

        return "Job #$this->id by @$user->username$nl**$this->title**$nl $this->description $nl In Progress 🔵$nl $this->estimate hrs ⏰";

    }

    public function sendMessage($user) {
        $text = $this->getMessageText($user);

        $msg = sendMessage($text, 39);


        $job = $this;
        self::withoutEvents(function () use ($job, $msg) {
            $job->update([
                             'message_id' => $msg->id
                         ]);

        });
    }

    public function jobs() {
        return $this->hasMany(Job::class, 'job_id', 'id');
    }

    public static function getOrderedJobs($jobs, &$result = []) {
        foreach ($jobs as $job) {
            $result[] = $job;
            $jobsOfJob = $job->jobs;
            if (count($jobsOfJob) > 0) {
                self::getOrderedJobs($jobsOfJob, $result); // Add children recursively
            }
        }
        return $result;
    }


    public function mentions() {
        return $this->hasMany(Mention::class);
    }

    public function parent() {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function tags() {
        return $this->belongsToMany(Tag::class);

    }

    public function start($user) {
        if ($this->status !== Constants::IN_PROGRESS) {


            $this->update([
                              'status'   => Constants::IN_PROGRESS,
                              'start_at' => now(),
                          ]);
            $user->update([
                              'active_job_id' => $this->id
                          ]);


            $user->jobs()->where('jobs.id', '!=', $this->id)->whereStatus(Constants::IN_PROGRESS)->update([
                                                                                                              'status' => Constants::PAUSED
                                                                                                          ]);


        }

        return $this;

    }


    public function end($user, $status = Constants::COMPLETED) {
        if ($this->status === Constants::IN_PROGRESS) {

            $now = now();

            $this->update([
                              'status'   => $status,
                              'end_at'   => $now,
                              'duration' => $this->duration + $this->start_at->diffInMinutes($now),
                          ]);
            if ($status === Constants::COMPLETED) {
                $user->update([
                                  'active_job_id' => NULL
                              ]);
            }


        }
        return $this;

    }

    public function activities() {
        return $this->hasMany(Activity::class);
    }

    public function getTime($user_id, $period = 'all_time') {

        $firstOfMonth = now()->firstOfMonth();

        $acts = $this->activities->where('user_id', $user_id);

        if ($period === 'this_month') {
            $acts = $acts->where('created_at', '>=', $firstOfMonth);
        }

        $sum_minutes = 0;
        foreach ($acts as $act) {


            $left_at = now();
            if ($act->left_at !== NULL) {
                $left_at = $act->left_at;
            }

            $diff = $act->join_at->diffInMinutes($left_at);
            $sum_minutes += $diff;


        }
        CarbonInterval::setCascadeFactors([
                                              'minute' => [60, 'seconds'],
                                              'hour'   => [60, 'minutes'],
                                          ]);

        return [
            //            'job'         => $this,
            'sum_minutes' => $sum_minutes,
            'sum_hours'   => CarbonInterval::minutes($sum_minutes)->cascade()->forHumans(NULL, TRUE),

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
