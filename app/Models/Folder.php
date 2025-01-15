<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model {

    protected $fillable = [
        'user_id',
        'title',
        'workspace_id'
    ];


    public function chats() {
        return $this->belongsToMany(Chat::class,'chat_user');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }
}
