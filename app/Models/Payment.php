<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


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


    protected function amount(): Attribute {
        return Attribute::make(get: function ($value) {
            if ($value === NULL) {

                $contract = $this->contract;

                $total_hours = $this->total_hour;
                return $contract->amount * ($total_hours['sum_minutes'] / 60);
            }
            return $value;
        });
    }

    protected function totalHours(): Attribute {
        return Attribute::make(get: function ($value) {
            if ($value === NULL) {
                $user = $this->user;

                $contract = $this->contract;

                return $user->getTime($contract->start_at, $contract->end_at, $contract->workspace_id);
            }
            return $value;
        });
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function contract() {
        return $this->belongsTo(Contract::class);
    }
}
