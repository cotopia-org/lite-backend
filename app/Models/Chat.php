<?php

namespace App\Models;

use App\Utilities\Constants;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model {

    //    protected $with = ['messages', 'users', 'workspace'];

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


    public function getTitle($user) {
        $title = $this->title;

        if ($this->type === Constants::DIRECT) {
            $pattern = "/\b($title)-(\w+)|(\w+)-($title)\b/";

            // Use preg_replace_callback to return the matching word
            return preg_replace_callback($pattern, function ($matches) {
                // Check which match is set and return the other word
                return $matches[2] ?? $matches[3];
            },                           $user->id);
        }

        return $this->title;
    }

    public function getChannelAttribute($value) {

        return 'chat-' . $this->id;

    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }


    public function owner() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function participants() {


        return $this->users;
    }


    public function pinnedMessages() {
        return $this->messages->where('is_pinned', TRUE);
    }

    public function mentionedMessages($user) {

        //TODO: has to change just usneen messages, but got mentions from chat->mentions->where(message_id > user last seen id) not from messages.
        $messagesIds = $this->messages->pluck('id');
        return $user->mentions->whereIn('id', $messagesIds);
    }

    public function unSeensCount($user) {
        // Messages that pinned and not seen
        // Message that user mentioned and not seen


        $last_message_seen_id = $this->users->where('user_id', $user->id)->first()->pivot->last_message_seen_id ?? 0;


        return $this->messages()->where('id', '>', $last_message_seen_id)->count();

    }

    public function unSeens($user) {
        // Messages that pinned and not seen
        // Message that user mentioned and not seen


        $last_message_seen_id = $this->users->where('user_id', $user->id)->first()->pivot->last_message_seen_id ?? 0;


        return $this->messages()->with('files', 'links', 'mentions')->where('id', '>', $last_message_seen_id)->get();

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
