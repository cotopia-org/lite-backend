<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\CalendarResource;
use App\Http\Resources\JobResource;
use App\Http\Resources\RoomListResource;
use App\Http\Resources\ScheduleResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\UserMinimalResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Activity;
use App\Models\Job;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceCreatedNotification;
use App\Notifications\WorkspaceJoinedNotification;
use App\Utilities\Constants;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class WorkspaceController extends Controller {
    public function all() {
        $user = auth()->user();

        return api(WorkspaceResource::collection($user->workspaces));
    }

    public function rooms(Workspace $workspace) {
        return api(RoomListResource::collection($workspace->rooms()->with([
                                                                              'users' => [
                                                                                  'avatar'
                                                                              ]
                                                                          ])->get()));
    }

    public function tags(Workspace $workspace) {

        return api(TagResource::collection($workspace->tags));
    }

    public function jobs(Workspace $workspace) {


        $jobs = $workspace->jobs()->whereNull('job_id')->get();
        $orderedJobs = Job::getOrderedJobs($jobs);

        return api(JobResource::collection($orderedJobs));
    }

    public function users(Workspace $workspace) {
        return api(UserResource::collection($workspace
                                                ->users()
                                                ->with('schedules', 'avatar', 'activeJob', 'activeJob.activities', 'contracts', 'contracts.payments')
                                                ->get()));
    }

    public function get(Workspace $workspace) {
        if (auth()->user()->tokenCan(Permission::WS_GET->value . '-' . $workspace->id)) {
            return api(WorkspaceResource::make($workspace));

        }
        return error('Permission Denied');
    }

    public function create(Request $request) {
        $request->validate(['title' => 'required']);
        /** @var User $user */
        $user = auth()->user();

        $workspace = Workspace::create($request->all());
        $workspace->joinUser($user, 'super-admin');


        $workspace->rooms()->create([
                                        'title'   => 'general',
                                        'user_id' => $user->id
                                    ]);


        //        $user->notify(new WorkspaceCreatedNotification($workspace));

        return api(WorkspaceResource::make($workspace));
    }

    public function update(Workspace $workspace, Request $request) {

        //TODO: has to check with sanctum permissions
        $workspace->update($request->all());

        sendSocket(Constants::workspaceUpdated, $workspace->channel, $workspace);

        return api(WorkspaceResource::make($workspace));

    }

    public function addRole(Workspace $workspace, Request $request) {

        $request->validate([
                               'role'    => 'required',
                               'user_id' => 'required',
                           ]);

        $user = auth()->user();
        if ($user->isSuperAdmin($workspace)) {

            $role = Role::findOrFail($request->role_id);
            $wsUser = User::findOrFail($request->user_id);

            $wsUser->giveRole($role, $workspace->id);


        }


        return api(WorkspaceResource::make($workspace));

    }

    public function schedules(Workspace $workspace) {


        $workspaceUsers = DB::table('user_workspace')->where('workspace_id', $workspace->id)->get()->pluck('user_id');

        $schedules = Schedule::whereIn('user_id', $workspaceUsers)->with([
                                                                             'user' => [
                                                                                 'schedules',
                                                                                 'avatar'
                                                                             ]
                                                                         ])->get();


        return api(ScheduleResource::collection($schedules));
    }

    public function leaderboard(Workspace $workspace) {


        $firstOfMonth = now()->firstOfMonth();

        $users = $workspace->users;
        $acts = DB::table('activities')->where('workspace_id', $workspace->id)
                  ->select('user_id', DB::raw('SUM(TIMESTAMPDIFF(SECOND, join_at, IFNULL(left_at, NOW())) / 60) as sum_minutes'), DB::raw('SUM(IF(job_id IS NULL, TIMESTAMPDIFF(SECOND, join_at, IFNULL(left_at, NOW())) / 60, 0)) as idle'), DB::raw('SUM(IF(job_id IS NOT NULL, TIMESTAMPDIFF(SECOND, join_at, IFNULL(left_at, NOW())) / 60, 0)) as working'))
                  ->where('created_at', '>=', $firstOfMonth)->groupBy('user_id')->get();
        $d = [];
        foreach ($acts as $act) {
            $user = $users->find($act->user_id);
            if ($user === NULL) {
                continue;
            }
            $d[] = [
                'sum_minutes'     => (float)$act->sum_minutes,
                'idle_minutes'    => (float)$act->idle,
                'working_minutes' => (float)$act->working,
                'user'            => $user,
            ];
        }
        return api(array_values($d));


    }

    public function join(Workspace $workspace) {
        /** @var User $user */
        $user = auth()->user();
        $workspace->joinUser($user);

        $user->notify(new WorkspaceJoinedNotification($workspace));

        return api(TRUE);
    }


}
