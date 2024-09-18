<?php

namespace App\Http\Controllers;

use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Models\User;
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
        $job = Job::create($request->all());

        $user->jobs()->attach($job, ['role' => 'owner']);

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

        if ($request->status === 'in_progress') {
            Job::whereStatus('in_progress')->update([
                                                        'status' => 'todo'
                                                    ]);
        }


        $job->update($request->all());

        $jobResource = JobResource::make($job);

        sendSocket('jobUpdated', $job->workspace->channel, $jobResource);

        return api($jobResource);
    }

    public function delete(Job $job)
    {
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

        $job->users()->detach($jobUser->id);

        return api(JobResource::make($job));

    }
}
