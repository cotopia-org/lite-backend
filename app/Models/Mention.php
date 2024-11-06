<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mention extends Model
{
    protected $fillable = [
        'user_id',
        'message_id',
        'chat_id',
        'start_position',
        'mentionable_type',
        'mentionable_id'
    ];

    protected $appends = [
        'mentioned_by'
    ];


    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function getMentionedByAttribute()
    {
        return $this->mentionable->mentionedBy();
    }

    public function mentionable()
    {
        return $this->morphTo();
    }
}
