<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Events\ChatCreated;
use App\Events\JobCreated;
use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Models\Tag;
use App\Models\User;
use App\Utilities\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    public function create(Request $request)
    {


        $request->validate([
                               'title'        => 'required',
                               'description'  => 'required',
                               'estimate'     => 'required',
                               'workspace_id' => 'required|exists:workspaces,id',
                               'job_id'       => 'required|exists:jobs,id',
                               'mentions'     => 'required',
                           ]);

        $user = auth()->user();

        $user->canDo(Permission::WS_ADD_JOB, $request->workspace_id);


        $req = $request->all();
        $req['level'] = 0;
        if ($request->job_id !== NULL) {
            $parent = Job::find($request->job_id);
            $req['level'] = $parent->level + 1;

        }
        $job = Job::create($req);


        if ($job->status === Constants::IN_PROGRESS) {


            DB::table('job_user')->where('user_id', $user->id)->where('status', Constants::IN_PROGRESS)->update([
                                                                                                                    'status' => Constants::PAUSED
                                                                                                                ]);

            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended',
                      'JobController@create');

            }
            acted($user->id, $user->workspace_id, $user->room_id, $job->id, 'job_started', 'JobController@create');

            $user->updateActiveJob($job->id);

        }


        $user->jobs()->attach($job, ['role' => 'owner', 'status' => $request->status]);

        //        event(new JobCreated($job));

        $job->createChat();

        $models = ['user' => User::class, 'tag' => Tag::class];
        $res = JobResource::make($job);

        if ($request->mentions) {
            foreach ($request->mentions as $mention) {
                $job->mentions()->create([
                                             'user_id'          => $user->id,
                                             'mentionable_type' => $models[$mention['type']],
                                             'mentionable_id'   => $mention['model_id'],

                                         ]);
                if ($mention['type'] === 'user') {
                    sendSocket('jobSuggested', User::find($mention['model_id'])->socket_id, $res);

                }
                if ($mention['type'] === 'tag') {
                    foreach (Tag::find($mention['model_id'])->users as $u) {
                        sendSocket('jobSuggested', $u->socket_id, $res);

                    }

                }
            }

        }


        sendSocket(Constants::jobCreated, $job->workspace->channel, $res);

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

        if ($job->old) {
            return error('Cant update old jobs, sorry!');
        }

        $user = auth()->user();

        $user->canDo(Permission::JOB_UPDATE, $job->workspace_id);


        if ($request->status !== Constants::IN_PROGRESS) {


            DB::table('job_user')->where('job_id', $job->id)->update([
                                                                         'status' => $request->status
                                                                     ]);

        }


        $req = $request->all();
        $req['level'] = 0;
        if ($request->job_id !== NULL) {
            $parent = Job::find($request->job_id);
            $req['level'] = $parent->level + 1;

        }


        $job->update($req);

        $jobResource = JobResource::make($job);

        sendSocket(Constants::jobUpdated, $job->workspace->channel, $jobResource);


        $currentMentionIds = $job->mentions()->pluck('id')->toArray();

        $newMentions = collect($request->mentions)->filter(fn ($mention) => !isset($mention['id']))->toArray();
        $existingMentions = collect($request->mentions)
            ->filter(fn ($mention) => isset($mention['id']))->pluck('id')->toArray();


        $mentionsToDelete = array_diff($currentMentionIds, $existingMentions);
        if (!empty($mentionsToDelete)) {
            $job->mentions()->whereIn('id', $mentionsToDelete)->delete();
        }

        $models = ['user' => User::class, 'tag' => Tag::class];

        foreach ($newMentions as $mention) {
            if (!isset($mentionData['id'])) {

                $job->mentions()->create([
                                             'user_id'          => $user->id,
                                             'mentionable_type' => $models[$mention['type']],
                                             'mentionable_id'   => $mention['model_id'],

                                         ]);
                if ($mention['type'] === 'user') {
                    sendSocket(Constants::jobSuggested, User::find($mention['model_id'])->socket_id, $jobResource);

                }
                if ($mention['type'] === 'tag') {
                    foreach (Tag::find($mention['model_id'])->users as $u) {
                        sendSocket(Constants::jobSuggested, $u->socket_id, $jobResource);

                    }

                }

            }
        }


        return api($jobResource);
    }


    public function updateStatus(Job $job, Request $request)
    {
        if ($job->old) {
            return error('Cant update old jobs, sorry!');
        }
        $request->validate([
                               'status' => [
                                   'required',
                                   Rule::in(Constants::IN_PROGRESS, Constants::PAUSED, Constants::COMPLETED),
                               ]
                           ]);
        $user = auth()->user();

        if ($request->status === Constants::IN_PROGRESS) {
            DB::table('job_user')->where('user_id', $user->id)->where('status', Constants::IN_PROGRESS)->update([
                                                                                                                    'status' => Constants::PAUSED
                                                                                                                ]);

            $jobResource = JobResource::make($job);

            sendSocket(Constants::jobUpdated, $job->workspace->channel, $jobResource);

            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended',
                      'JobController@updateStatus');

            }
            acted($user->id, $user->workspace_id, $user->room_id, $job->id, 'job_started',
                  'JobController@updateStatus');

            $user->updateActiveJob($job->id);

        } elseif ($job->id === $user->active_job_id) {
            acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended',
                  'JobController@updateStatus');
            $user->updateActiveJob(NULL);

        }


        DB::table('job_user')->where('user_id', $user->id)->where('job_id', $job->id)->update([
                                                                                                  'status'     => $request->status,
                                                                                                  'updated_at' => now()
                                                                                              ]);
        $jobResource = JobResource::make($job);
        sendSocket(Constants::jobUpdated, $job->workspace->channel, $jobResource);

        return api($jobResource);


    }


    public function accept(Job $job)
    {
        $user = auth()->user();
        if (!$job->joinable) {
            return error('This job is parent only, so no one can join to it.');
        }

        $job_user = DB::table('job_user')->where('user_id', $user->id)->where('job_id', $job->id)->first();
        if ($job_user === NULL) {
            DB::table('job_user')->where('user_id', $user->id)->where('status', Constants::IN_PROGRESS)->update([
                                                                                                                    'status' => Constants::PAUSED
                                                                                                                ]);

            $user->jobs()->attach($job, ['role' => 'member', 'status' => Constants::IN_PROGRESS]);


            if ($user->active_job_id !== NULL) {
                acted($user->id, $user->workspace_id, $user->room_id, $user->active_job_id, 'job_ended',
                      'JobController@accept');

            }
            acted($user->id, $user->workspace_id, $user->room_id, $job->id, 'job_started', 'JobController@accept');
            $user->updateActiveJob($job->id);


            $jobFolder = $user->folders()->where('title', 'Jobs')->first();
            $job->chat->users()->attach($user->id, ['role' => 'member', 'folder_id' => $jobFolder->id]);
            event(new ChatCreated($job->chat));


        }
        return api(TRUE);


    }

    public function dismiss(Job $job)
    {
        if (!$job->joinable) {
            return error('This job is parent only, so no one can dismiss it.');
        }
        $user = auth()->user();
        $user->jobs()->attach($job, ['role' => 'member', 'status' => Constants::DISMISSED]);

        return api(TRUE);


    }

    public function jobs(Job $job)
    {

        return api(JobResource::collection($job->jobs));
    }


    public function addMember(Job $job, Request $request) {}

    public function removeMember(Job $job, Request $request) {}
}
