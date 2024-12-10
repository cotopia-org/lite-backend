<?php

namespace App\Http\Controllers;

use App\Events\JobCreated;
use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Models\Tag;
use App\Models\User;
use App\Utilities\Constants;
use Illuminate\Http\Request;

class JobController extends Controller {
    public function create(Request $request) {

        return error('Sorry cant now.');
        $request->validate([
                               'title'        => 'required',
                               'description'  => 'required',
                               'estimate'     => 'required',
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

        if ($job->status === Constants::IN_PROGRESS) {


            $user->jobs()->where('jobs.id', '!=', $job->id)->whereStatus(Constants::IN_PROGRESS)->update([
                                                                                                             'status' => Constants::PAUSED
                                                                                                         ]);


            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended', 'JobController@create');

            }
            acted($user->id, $user->workspace_id, $user->room_id, $job->id, 'job_started', 'JobController@create');

            $user->updateActiveJob($job->id);

        }


        $user->jobs()->attach($job, ['role' => 'owner', 'status' => $request->status]);

        //        event(new JobCreated($job));

        $job->sendMessage($user);


        if ($request->mentions) {
            $models = ['user' => User::class, 'tag' => Tag::class];
            foreach ($request->mentions as $mention) {
                $job->mentions()->create([
                                             'user_id'          => $user->id,
                                             'mentionable_type' => $models[$mention['type']],
                                             'mentionable_id'   => $mention['model_id'],

                                         ]);
            }
        }


        return api(JobResource::make($job));
    }

    public function get(Job $job) {
        $user = auth()->user();
        if (!$user->jobs->contains($job)) {
            abort(404);
        }
        //TODO: code upper, need to changed to user->can('update-job-1') method.

        return api(JobResource::make($job));

    }

    public function update(Job $job, Request $request) {
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
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended', 'JobController@update');

            }
            acted($user->id, $user->workspace_id, $user->room_id, $job->id, 'job_started', 'JobController@update');


            $user->updateActiveJob($job->id);


        } elseif ($job->id === $user->active_job_id) {

            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended', 'JobController@update');

            $user->updateActiveJob();

        }


        $req = $request->all();
        $req['level'] = 0;
        if ($request->job_id !== NULL) {
            $parent = Job::find($request->job_id);
            $req['level'] = $parent->level + 1;

        }


        $job->update($req);

        $jobResource = JobResource::make($job);

        sendSocket('jobUpdated', $job->workspace->channel, $jobResource);


        $currentMentionIds = $job->mentions()->pluck('id')->toArray();

        $newMentions = collect($request->mentions)->filter(fn($mention) => !isset($mention['id']))->toArray();
        $existingMentions = collect($request->mentions)
            ->filter(fn($mention) => isset($mention['id']))->pluck('id')->toArray();


        $mentionsToDelete = array_diff($currentMentionIds, $existingMentions);
        if (!empty($mentionsToDelete)) {
            $job->mentions()->whereIn('id', $mentionsToDelete)->delete();
        }


        foreach ($newMentions as $mention) {
            if (!isset($mentionData['id'])) {
                $models = ['user' => User::class, 'tag' => Tag::class];

                $job->mentions()->create([
                                             'user_id'          => $user->id,
                                             'mentionable_type' => $models[$mention['type']],
                                             'mentionable_id'   => $mention['model_id'],

                                         ]);
            }
        }


        return api($jobResource);
    }

    public function delete(Job $job) {

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


    public function accept(Job $job) {
        $user = auth()->user();

        $user->jobs()->attach($job, ['role' => 'member', 'status' => Constants::IN_PROGRESS]);


    }

    public function removeUser(Job $job, Request $request) {
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
