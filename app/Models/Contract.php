<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model {

    protected $fillable = [
        'type',
        'amount',
        'currency',
        'start_at',
        'end_at',
        'auto_renewal',
        'renewal_count',
        'renew_time_period_type',
        'renew_time_period',
        'renew_notice',
        'user_status',
        'contractor_status',
        'min_hours',
        'max_hours',
        'payment_method',
        'payment_address',
        'payment_period',
        'role',
        'user_sign_status',
        'contractor_sign_status',
        'user_id',
        'workspace_id',
        'in_schedule',
        'content',
        'schedule_id',
    ];

    protected function content(): Attribute {
        return Attribute::make(get: fn($value) => json_decode($value),//            set: fn($value) => json_encode($value),
        );
    }

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    protected $dates = ['start_at', 'end_at'];


    public function user() {
        return $this->belongsTo(User::class);
    }

    public function schedule() {
        return $this->belongsTo(Schedule::class);
    }

    public function text() {

        $text = [];

        foreach ($this->content as $content) {

            $text[$content] = __('contracts.content.' . $content, [
                'workspace_name'         => 'Tester',
                'username'               => 'Katerou22',
                'start_at'               => 'today',
                'end_at'                 => 'tomorrow',
                'per_hour'               => '10',
                'min_hours'              => '50',
                'max_hours'              => '200',
                'renewal_count'          => 2,
                'renew_time_period_type' => 'monthly',
                'renew_notice'           => '10',
                'payment_method'         => 'trc20',
                'payment_period'         => 'month',
                'payment_address'        => 'TESTPAYMENTADDRESS',
            ],                   'en');

        }

        return $text;

    }

    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }

    public function renew() {


        $attrs = $this->toArray();
        $attrs['start_at'] = $this->start_at->addMonth();
        $attrs['end_at'] = $this->start_at->addMonth()->endOfMonth();
        $contract = self::create($attrs);


        $payment = Payment::create([
                                       'status'      => 'pending',
                                       'amount'      => NULL,
                                       'total_hours' => NULL,
                                       'type'        => 'salary',
                                       'user_id'     => $this->user_id,
                                       'contract_id' => $contract->id
                                   ]);
    }

    public function payments() {
        return $this->hasMany(Payment::class);
    }
}
