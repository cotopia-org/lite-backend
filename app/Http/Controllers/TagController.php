<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
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


        $user = auth()->user();
        $user->canDo(Permission::WS_ADD_TAG, $request->workspace_id);


        $tag = Tag::create($request->all());

        return api(TagResource::make($tag));
    }

    public function addMember(Tag $tag, Request $request)
    {


        $request->validate([
                               'user_id' => 'required|exists:users,id',
                           ]);


        $tag->users()->attach($request->user_id);
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
