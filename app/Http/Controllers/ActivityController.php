<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ActivityController extends Controller {

    public function get(User $user, Request $request) {


        $acts = $user
            ->activities()->orderBy('id', 'ASC')->where('created_at', '>=', $request->start_at)
            ->where('created_at', '<=', $request->end_at)->get()->map(function ($activity) {
                return [
                    'id'              => $activity->id,
                    'join_at'         => $activity->join_at->timezone('Asia/Tehran')->toDateTimeString(),
                    'left_at'         => $activity->left_at?->timezone('Asia/Tehran')->toDateTimeString(),
                    'job_id'          => $activity->job_id,
                    'room_id'         => $activity->room_id,
                    'workspace_id'    => $activity->workspace_id,
                    'diff'            => gmdate('H:i:s', $activity->join_at?->diffInSeconds($activity->left_at)),
                    'diff_in_minutes' => $activity->join_at?->diffInMinutes($activity->left_at),
                    'diff_in_hours'   => $activity->join_at?->diffInHours($activity->left_at),
                    'data'            => $activity->data,

                ];
            });

        return api($acts);

    }
}
