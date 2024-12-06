<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model {

    protected $fillable = [
        'status',
        'amount',
        'total_hours',
        'bonus',
        'round',
        'type',
        'user_id',
        'contract_id'
    ];


    public function user() {
        return $this->belongsTo(User::class);
    }

    public function contract() {
        return $this->belongsTo(Contract::class);
    }
}
