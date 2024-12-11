<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ActivityController extends Controller {

    public function get(User $user, Request $request) {


        $acts = $user
            ->activities()->where('created_at', '>=', $request->start_at)->where('created_at', '<=', $request->end_at)
            ->get()->map(function ($activity) {
                return [
                    'id'           => $activity->id,
                    'join_at'      => $activity->join_at->toDateTimeString(),
                    'left_at'      => $activity->left_at->toDateTimeString(),
                    'job_id'       => $activity->job_id,
                    'room_id'      => $activity->room_id,
                    'workspace_id' => $activity->workspace_id,
                    'diff'         => $activity->left_at->diffInMinutes($this->join_at),

                ];
            });

        return api($acts);

    }
}
