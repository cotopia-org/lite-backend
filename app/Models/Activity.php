<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model {
    protected $fillable = [
        'join_at',
        'left_at',
        'user_id',
        'workspace_id',
        'room_id',
        'data',
    ];


    protected $casts = [
        'join_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function workspace() {
        return $this->belongsTo(Workspace::class);

    }

    public function room() {
        return $this->belongsTo(Room::class);

    }
}
