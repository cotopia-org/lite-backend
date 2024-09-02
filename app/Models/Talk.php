<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Talk extends Model
{


    protected $fillable = [
        'user_id',
        'owner_id',
        'response'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function owner()
    {
        return $this->belongsTo(User::class);

    }
}
