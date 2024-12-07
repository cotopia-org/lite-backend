<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mention extends Model {
    protected $fillable = [
        'user_id',
        'message_id',
        'chat_id',
        'start_position',
        'mentionable_type',
        'mentionable_id',
        'job_id',
    ];

    protected $appends = [
        'mentioned_by'
    ];

    public function job() {
        return $this->belongsTo(Job::class);
    }

    public function type() {
        switch (typeOf($this->mentionable)) {
            case User::class:
                return 'user';

            case Tag::class:
                return 'tag';

            case Workspace::class:
                return 'workspace';

            case Room::class:
                return 'room';

            default:
                break;
        }
    }

    public function title() {
        switch (typeOf($this->mentionable)) {
            case User::class:
                return $this->mentionable->username;

            case Tag::class:
            case Workspace::class:
            case Room::class:
                return $this->mentionable->title;

            default:
                break;
        }
    }

    public function message() {
        return $this->belongsTo(Message::class);
    }


    public function chat() {
        return $this->belongsTo(Chat::class);
    }

    public function getMentionedByAttribute() {
        return $this->mentionable->mentionedBy();
    }

    public function mentionable() {
        return $this->morphTo();
    }
}
