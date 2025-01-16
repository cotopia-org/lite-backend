<?php

namespace App\Models;

use App\Utilities\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{

    //    protected $with = ['messages', 'users', 'workspace'];
    use SoftDeletes;

    protected $fillable = [
        'title',
        'active',
        'type',
        'password',
        'workspace_id',
        'user_id',
        'deleted_at',
        'folder_id',
    ];
    protected $appends = [
        'channel'
    ];


    public function getTitle($user)
    {
        $title = $this->title;
        $id = $user->id;

        if ($this->type === Constants::DIRECT) {


            $names = explode('-', $title);
            $sum = (int) $names[0] + (int) $names[1];
            $user_id = ($id === (int) $names[0] || $id === (int) $names[1]) ? $sum - $id : NULL;

            return $this->users->find($user_id)->name;
        }

        return $this->title;
    }

    public function getChannelAttribute($value)
    {

        return 'chat-' . $this->id;

    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }


    public function addMember($user, $role = 'member')
    {
        $this->users()->attach($user->id, ['role' => $role]);

    }

    public function mentions()
    {
        return $this->hasMany(Mention::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function participants()
    {


        return $this->users;
    }


    public function pinnedMessages()
    {
        return $this->messages->where('is_pinned', TRUE);
    }

    public function mentionedMessages($user)
    {


        $last_message_seen_id = $this->users->where('user_id', $user->id)->first()->pivot->last_message_seen_id ?? 0;
        return $this->mentions
            ->where('mentionable_type', User::class)->where('mentionable_id', $user->id)
            ->where('message_id', '>', $last_message_seen_id);
    }

    public function unSeensCount($user)
    {
        // Messages that pinned and not seen
        // Message that user mentioned and not seen


        $pivot = $this->users->find($user->id)->pivot;
        $last_message_seen_id = $pivot->last_message_seen_id ?? 0;
        $joined_at = $pivot->created_at;
        return $this
            ->messages()->where('created_at', '>=', $joined_at)->where('id', '>', $last_message_seen_id)->count();

    }


    public function avatar()
    {
        return $this->morphOne(File::class, 'fileable');
    }

    public function sawMessages($user)
    {

        $pivot = $this->users->find($user->id)->pivot;
        $last_message_seen_id = $pivot->last_message_seen_id ?? 0;
        $joined_at = $pivot->created_at;
        return $this
            ->messages()->orderBy('id', 'DESC')->withTrashed()->with([
                                                                         'links',
                                                                         'mentions',
                                                                         'user',
                                                                         'files',
                                                                     ])->where('id', '<=', $last_message_seen_id)
            ->where('created_at', '>=', $joined_at);

    }

    public function unSeens($user)
    {


        $pivot = $this->users->find($user->id)->pivot;
        $last_message_seen_id = $pivot->last_message_seen_id ?? 0;
        $joined_at = $pivot->created_at;

        return $this
            ->messages()->with('files', 'links', 'mentions')->where('created_at', '>=', $joined_at)
            ->where('id', '>', $last_message_seen_id)->get();

    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'last_message_seen_id', 'muted', 'folder_id')
                    ->withTimestamps();
    }


    public function folder()
    {
        return $this->belongsTo(Folder::class);
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
