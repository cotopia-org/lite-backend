<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Redirect;

class Message extends Model {


    use SoftDeletes;

    protected $with = ['seens'];

    protected $fillable = [
        'text',
        'room_id',
        'user_id',
        'is_edited',
        'reply_to',
        'is_pinned',
        'created_at',
        'deleted_at',
        'updated_at',
        'nonce_id',
    ];

    public function room() {
        return $this->belongsTo(Room::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function replyTo() {
        return $this->belongsTo(__CLASS__, 'reply_to');
    }

    public function files() {
        return $this->morphMany(File::class, 'fileable');
    }

    public function mentions() {
        return $this->hasMany(Mention::class);
    }

    public function replies() {
        return $this->hasMany(__CLASS__, 'reply_to');
    }

    public function seens() {
        return $this->hasMany(Seen::class);
    }

    public function links() {
        return $this->hasMany(Link::class);
    }

    public function saw($user) {
        if ($user === NULL) {
            return FALSE;
        }
        return $this->seens->where('user_id', $user->id)->first() !== NULL;

    }
}
