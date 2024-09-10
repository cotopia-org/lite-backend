<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{


    protected $fillable = [
        'title',
        'active',
        'is_private',
        'password',
        'status',
        'workspace_id',
    ];


    public function participants()
    {
        return $this->belongsToMany(User::class, 'chat_user');
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
