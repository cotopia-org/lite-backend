<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Act extends Model {
    protected $fillable = [
        'type',
        'user_id',
        'workspace_id',
        'job_id',
        'room_id',
        'description'
    ];


    //Types [time_started,time_ended,job_started,job_ended,connected,disconnected]


}
