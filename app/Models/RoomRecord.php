<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'is_audio', 'is_video', 'url', 'status',
    ];
}
