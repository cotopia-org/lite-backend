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
        'reportable_id'
    ];


    public function reportable()
    {
        return $this->morphTo();
    }
}
