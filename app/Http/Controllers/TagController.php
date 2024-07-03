<?php

namespace App\Http\Controllers;

use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    //TODO: has to check sanctum.
    public function create(Request $request)
    {
        $request->validate([
            'title'        => 'required',
            'workspace_id' => 'required|exists:workspaces,id',
        ]);

        $tag = Tag::create($request->all());

        return api(TagResource::make($tag));
    }

    public function get(Tag $tag)
    {
        $user = auth()->user();

        return api(TagResource::make($tag));

    }

    public function update(Tag $tag, Request $request)
    {

        $tag->update($request->all());

        return api(TagResource::make($tag));
    }
}
