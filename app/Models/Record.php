<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'egress_id', 'room_id', 'is_audio', 'is_video', 'url', 'status', 'type', 'started_at', 'ended_at',
    ];
}
