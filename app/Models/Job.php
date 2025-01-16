<?php

namespace App\Models;

use App\Events\ChatCreated;
use App\Utilities\Constants;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
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
        'chat_id',
        'job_id',
        'level',
        'old',
        'joinable',
    ];

    protected $casts = [
        'end_at'   => 'datetime',
        'start_at' => 'datetime',
    ];


    public static function getMessageText($user, $job)
    {
        $nl = PHP_EOL;


        return "'$job->title' by @$user->username $nl  $job->description";

    }

    public function sendMessage($user)
    {
        $text = self::getMessageText($user, $this);

        $msg = sendMessage($text, 39);


        $job = $this;
        self::withoutEvents(function () use ($job, $msg) {
            $job->update([
                             'message_id' => $msg->id
                         ]);

        });
    }


    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }


    public function createChat()
    {


        $owner = $this->users->first();
        $chat = Chat::create([
                                 'title'        => $this->title,
                                 'active'       => TRUE,
                                 'type'         => Constants::GROUP,
                                 'workspace_id' => $this->workspace_id,
                                 'user_id'      => $owner->id,
                             ]);


        $this->update([
                          'chat_id' => $chat->id
                      ]);


        $jobFolder = $owner->folders()->where('title', 'Jobs')->first();
        $chat->users()->attach($owner->id, ['role' => 'super-admin', ['folder_id' => $jobFolder->id]]);
        event(new ChatCreated($chat));
        $this->sendMessage($owner);


    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'job_id', 'id');
    }

    public static function getOrderedJobs($jobs, &$result = [])
    {
        foreach ($jobs as $job) {
            $result[] = $job;
            $jobsOfJob = $job->jobs;
            if (count($jobsOfJob) > 0) {
                self::getOrderedJobs($jobsOfJob, $result); // Add children recursively
            }
        }
        return $result;
    }


    public function mentions()
    {
        return $this->hasMany(Mention::class);
    }

    public function parent()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);

    }

    public function start($user)
    {
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


    public function end($user, $status = Constants::COMPLETED)
    {
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

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function acts()
    {
        return $this->hasMany(Act::class);
    }

    public function getTime($user_id, $period = 'all_time')
    {


        //        \Carbon\CarbonInterval::setCascadeFactors([
        //                                                      'seconds' => [1_000, 'milliseconds'],
        //
        //                                                      'minute' => [60, 'seconds'],
        //                                                      'hour'   => [60, 'minutes'],
        //                                                  ]);
        $now = now();


        //        $starts = Act::where('type', 'job_started')->where('job_id', $this->id)
        //                     ->where('user_id', $user_id)->orderBy('id', 'ASC')->get();

        $query = Act::whereIn('type', ['job_started', 'job_ended'])->where('job_id', $this->id)
                    ->where('user_id', $user_id)->orderBy('id', 'ASC');

        if ($period === 'this_month') {
            $query = $query->where('created_at', '>=', now()->firstOfMonth());
        }
        $acts = $query->get();

        $minutes = 0;
        foreach ($acts as $act) {

            if ($act->type === 'job_started') {
                $end = $acts->where('id', '>', $act->id)->where('type', 'job_ended')->first();
                if ($end === NULL) {
                    $minutes += $act->created_at->diffInMinutes($now);
                } else {
                    $minutes += $act->created_at->diffInMinutes($end->created_at);

                }
            }
        }
        return $minutes;
    }

    public function lastActivity()
    {

        return $this->activities()->whereNull('left_at')->first();

    }

    public function joinUser($user, $role = 'developer')
    {
        if (!$this->users->contains($user->id)) {
            $this->users()->attach($user, ['role' => $role]);
            //TODO: Socket, user joined to job.

        }

        return $this;

    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'status')->withTimestamps();
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }


}
