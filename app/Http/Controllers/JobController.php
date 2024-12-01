<?php

namespace App\Http\Controllers;

use App\Events\JobCreated;
use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Models\User;
use App\Utilities\Constants;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
                               'title'        => 'required',
                               'description'  => 'required',
                               'workspace_id' => 'required|exists:workspaces,id',
                           ]);

        $user = auth()->user();

        $req = $request->all();
        $req['level'] = 0;
        if ($request->job_id !== NULL) {
            $parent = Job::find($request->job_id);
            $req['level'] = $parent->level + 1;

        }
        $job = Job::create($req);

        if ($request->tags) {
            $job->tags()->attach($request->tags);
        }

        if ($job->status === Constants::IN_PROGRESS) {


            $user->jobs()->where('jobs.id', '!=', $job->id)->whereStatus(Constants::IN_PROGRESS)->update([
                                                                                                             'status' => Constants::PAUSED
                                                                                                         ]);


            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended',
                      'JobController@create');

            }
            acted($user->id, $user->workspace_id, $user->room_id, $job->id, 'job_started', 'JobController@create');

            $user->updateActiveJob($job->id);

        }


        $user->jobs()->attach($job, ['role' => 'owner']);

        //        event(new JobCreated($job));

        $text = "Job #$job->id by @$user->username

**$job->title**

$job->description

In Progress 🔵

$job->estimate hrs ⏰
";

        $msg = sendMessage($text, 39);


        Job::withoutEvents(function () use ($job, $msg) {
            $job->update([
                             'message_id' => $msg->id
                         ]);

        });

        return api(JobResource::make($job));
    }

    public function get(Job $job)
    {
        $user = auth()->user();
        if (!$user->jobs->contains($job)) {
            abort(404);
        }
        //TODO: code upper, need to changed to user->can('update-job-1') method.

        return api(JobResource::make($job));

    }

    public function update(Job $job, Request $request)
    {
        $user = auth()->user();

        if (!$user->jobs->contains($job)) {
            abort(404);
        }
        //TODO: code upper, need to changed to user->can('update-job-1') method.

        if ($request->status === Constants::IN_PROGRESS) {


            foreach ($user->jobs()->whereStatus(Constants::IN_PROGRESS)->get() as $j) {
                $j->update([
                               'status' => Constants::PAUSED
                           ]);
            }


            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended',
                      'JobController@update');

            }
            acted($user->id, $user->workspace_id, $user->room_id, $job->id, 'job_started', 'JobController@update');


            $user->updateActiveJob($job->id);


        } elseif ($job->id === $user->active_job_id) {

            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended',
                  'JobController@update');

            $user->updateActiveJob();

        }


        $req = $request->all();
        $req['level'] = 0;
        if ($request->job_id !== NULL) {
            $parent = Job::find($request->job_id);
            $req['level'] = $parent->level + 1;

        }


        $job->update($req);
        if ($request->tags) {
            $job->tags()->sync($request->tags);
        }
        $jobResource = JobResource::make($job);

        sendSocket('jobUpdated', $job->workspace->channel, $jobResource);


        return api($jobResource);
    }

    public function delete(Job $job)
    {

        return api(TRUE);
        $user = auth()->user();

        if (!$user->jobs->contains($job)) {
            abort(404);
        }
        //TODO: code upper, need to changed to user->can('update-job-1') method.
        $job->users()->detach();
        $job->delete();

        return api(TRUE);
    }


    public function removeUser(Job $job, Request $request)
    {
        $request->validate([
                               'user_id' => 'required|exists:users,id',
                           ]);

        $user = auth()->user();
        if (!$user->jobs->contains($job)) {
            abort(404);
        }

        $jobUser = User::find($request->user_id);

        if ($jobUser->active_job_id === $job->id) {
            $jobUser->update(['active_job_id' => NULL]);
        }

        $job->users()->detach($jobUser->id);

        return api(JobResource::make($job));

    }
}
