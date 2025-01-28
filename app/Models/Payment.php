<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;


class Payment extends Model
{

    use SoftDeletes;

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


    protected function amount(): Attribute
    {
        return Attribute::make(get: function ($value) {
            if ($value === NULL) {

                $contract = $this->contract;

                $total_hours = $this->total_hours;
                return $contract->amount * ($total_hours['sum_minutes'] / 60);
            }
            return $value;
        });
    }


    protected function status(): Attribute
    {
        return Attribute::make(get: function ($value) {


            $contract = $this->contract;

            if (!$contract->end_at->isPast()) {
                return 'ongoing';
            }
            return $value;
        });
    }

    protected function totalHours(): Attribute
    {
        return Attribute::make(get: function ($value) {
            if ($value === NULL) {
                $user = $this->user;

                $contract = $this->contract;

                return $user->getTimeWithSchedule($contract);
//                $time = $user->calculateCommitment()['done'];
//
//
//                \Carbon\CarbonInterval::setCascadeFactors([
//                                                              'minute' => [60, 'seconds'],
//                                                              'hour'   => [60, 'minutes'],
//                                                          ]);
//                return [
//                    'sum_minutes' => $time,
//                    'sum_hours'   => \Carbon\CarbonInterval::minutes($time)->cascade()->forHumans(),
//
//                ];
            }
            return [
                'sum_minutes' => $value * 60
            ];
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
