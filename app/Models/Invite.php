<?php

namespace App\Models;

use App\Http\Resources\JobResource;
use App\Http\Resources\RoomResource;
use App\Http\Resources\WorkspaceResource;
use App\Utilities\Codeable;
use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    use Codeable;

    protected $fillable = [
        'owner_id',
        'user_id',
        'status',
        'inviteable_type',
        'inviteable_id',
    ];


    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        if ($this->inviteable instanceof Workspace) {

            return 'workspace';
        }
        if ($this->inviteable instanceof Job) {

            return 'job';
        }
        if ($this->inviteable instanceof Room) {

            return 'room';
        }
    }

    public function getResponseModel($inviteable = NULL)
    {
        if ($inviteable === NULL) {
            $inviteable = $this->inviteable;
        }
        if ($this->inviteable instanceof Workspace) {

            return WorkspaceResource::make($inviteable);
        }
        if ($this->inviteable instanceof Job) {

            return JobResource::make($inviteable);
        }
        if ($this->inviteable instanceof Room) {

            return RoomResource::make($inviteable);
        }
    }

    public function inviteable()
    {
        return $this->morphTo();
    }

    public function isForUser($user)
    {
        return $this->user_id === $user?->id;
    }
}
