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
                    'id'           => $this->id,
                    'join_at'      => $this->join_at->toDateTimeString(),
                    'left_at'      => $this->left_at->toDateTimeString(),
                    'job_id'       => $this->job_id,
                    'room_id'      => $this->room_id,
                    'workspace_id' => $this->workspace_id,
                    'diff'         => $this->left_at->diffInMinutes($this->join_at),

                ];
            });

        return api($acts);

    }
}
