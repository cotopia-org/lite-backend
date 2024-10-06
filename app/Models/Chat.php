<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model {

    protected $with = ['messages', 'users', 'workspace'];

    protected $fillable = [
        'title',
        'active',
        'type',
        'password',
        'workspace_id',
        'user_id',
    ];
    protected $appends = [
        'channel'
    ];

    public function getChannelAttribute($value) {

        return 'chat-' . $this->id;

    }

    public function lastMessage() {
        return $this
            ->messages()->orderByDesc('id')->first();

    }


    public function owner() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function participants() {


        return $this->users;
    }


    public function pinnedMessages() {
        return $this
            ->messages()->where('is_pinned', TRUE)->get();
    }

    public function mentionedMessages($user) {


        $messagesIds = $this
            ->unSeens($user, FALSE)->pluck('id');
        return $user
            ->mentions()->whereIn('id', $messagesIds)->get();
    }

    public function unSeens($user) {
        // Messages that pinned and not seen
        // Message that user mentioned and not seen


        $last_message_seen_id = $this->users->where('user_id', $user->id)->first()->pivot->last_message_seen_id ?? 0;


        return $this
            ->messages()->where('id', '>', $last_message_seen_id);

    }

    public function users() {
        return $this->belongsToMany(User::class)->withPivot('role', 'last_message_seen_id');
    }


    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }


    public function messages() {
        return $this->hasMany(Message::class);
    }
}
