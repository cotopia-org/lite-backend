<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller {
    public function search(Request $request) {


        $q = $request->q;

        $user = auth()->user();

        $workspace = $user->workspace;

        $users = $workspace->users();
        $tags = $workspace->tags();

        $users = $users->where('username', 'LIKE', '%' . $q . '%')->get()->map(function ($user) {
            return [
                'model_id' => $user->id,
                'title'    => $user->username,
                'type'     => 'user',

            ];
        });

        $tags = $tags->where('title', 'LIKE', '%' . $q . '%')->get()->map(function ($tag) {
            return [
                'model_id' => $tag->id,
                'title'    => $tag->title,
                'type'     => 'tag'
            ];
        });


        return api(array_merge($users->toArray(), $tags->toArray()));
    }
}
