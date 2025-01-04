<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model {
    protected $fillable = [
        'title',
        'workspace_id',
    ];

    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }

    public function users() {
        return $this->belongsToMany(User::class);
    }


    public function mentions() {
        return $this->morphMany(Mention::class, 'mentionable');
    }
}
