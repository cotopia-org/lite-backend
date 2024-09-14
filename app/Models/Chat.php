<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{


    protected $fillable = [
        'title',
        'active',
        'type',
        'password',
        'workspace_id',
        'user_id',
    ];


    public function lastMessage()
    {
        return $this->messages()->orderByDesc('id')
                    ->first();

    }


    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function participants()
    {
        if ($this->type === 'direct' && $this->workspace_id === NULL) {
            return User::find(explode('-', $this->title));

        }


        if ($this->type === 'group' && $this->workspace_id !== NULL) {
            return $this->workspace()->users;

        }

        return $this->users;
    }


    public function unSeens($user)
    {
        // Messages that pinned and not seen
        // Message that user mentioned and not seen
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role');
    }


    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }


    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
