<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ActivityController extends Controller {

    public function get(User $user, Request $request) {


        $acts = $user->activities()->where('created_at', '>=', $request->start_at)
                     ->where('created_at', '>=', $request->end_at)->get();

        return api($acts);

    }
}
