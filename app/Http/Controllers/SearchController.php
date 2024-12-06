<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller {
    public function search(Request $request) {


        $q = $request->q;


        $users = User::where('username', 'LIKE', '%' . $q . '%')->get()->map(function ($user) {
            return [
                'id'    => $user->id,
                'title' => $user->username,
                'type'  => 'user',

            ];
        });

        $tags = Tag::where('title', 'LIKE', '%' . $q . '%')->get()->map(function ($tag) {
            return [
                'id'    => $tag->id,
                'title' => $tag->title,
                'type'  => 'tag'
            ];
        });


        return api($users->merge($tags->toArray()));
    }
}
