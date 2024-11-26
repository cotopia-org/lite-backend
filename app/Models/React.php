<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class React extends Model {
    protected $fillable = [
        'user_id',
        'message_id',
        'chat_id',
        'emoji',
    ];


    public function user() {
        return $this->belongsTo(User::class);
    }

    public function message() {
        return $this->belongsTo(Message::class);
    }

    public function chat() {
        return $this->belongsTo(Chat::class);
    }
}
