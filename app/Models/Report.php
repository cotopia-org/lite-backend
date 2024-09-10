<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{

    protected $fillable = [
        'title',
        'description',
        'type',
        'reportable_type',
        'reportable_id',
        'user_id'
    ];

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reportable()
    {
        return $this->morphTo();
    }
}
