<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model {

    protected $fillable = [
        'type',
        'user_id',
        'contract_id',
        'workspace_id',
        'start_at',
        'end_at',
        'timezone',
        'title',
    ];


    protected $casts = ['start_at', 'end_at'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function contract() {
        return $this->belongsTo(Contract::class);
    }


    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }

}
