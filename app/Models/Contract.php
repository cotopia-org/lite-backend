<?php

namespace App\Models;

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
        'text',
    ];


    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    protected $dates = ['start_at', 'end_at'];


    public function user() {
        return $this->belongsTo(User::class);
    }


    public function text() {

        return __('contracts.per_hour', [
            'workspace_name' => $this->workspace->title,
            'username'       => $this->user->username,
            'start_at'       => $this->start_at->toDateTimeString(),
            'end_at'         => $this->end_at->toDateTimeString(),
            'per_hour'       => $this->amount,
            'min_hours'      => $this->min_hours,
            'max_hours'      => $this->max_hours
        ],        'en');
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
