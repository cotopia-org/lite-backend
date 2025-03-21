<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model {

    use SoftDeletes;

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
        'min_commitment_percent',
        'in_job'
    ];

    protected function content(): Attribute {
        return Attribute::make(set: fn($value) => json_encode($value),//            set: fn($value) => json_encode($value),
        );
    }

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    protected $dates = ['start_at', 'end_at'];

    public function status() {


        $userActiveContract = $this->user->activeContract();
        if ($userActiveContract !== NULL && $userActiveContract->id === $this->id) {
            return 'active';
        }
        if ($this->end_at->isPast()) {
            return 'expired';
        }
        if ($this->contractor_sign_status && !$this->user_sign_status) {
            return 'waiting_user_sign';
        }

        if (!$this->contractor_sign_status && $this->user_sign_status) {
            return 'waiting_admin_sign';
        }
        if ($this->contractor_sign_status && $this->user_sign_status) {
            return 'signed';
        }

        return 'draft';
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function schedule() {
        return $this->hasOne(Schedule::class);
    }

    public function text() {

        $text = [];

        foreach (json_decode($this->content) as $content) {

            $text[$content] = __('contracts.content.' . $content, [
                'workspace_name'         => $this->workspace->title,
                'username'               => $this->user->username,
                'start_at'               => $this->start_at->toDateTimeString(),
                'end_at'                 => $this->end_at->toDateTimeString(),
                'per_hour'               => $this->amount,
                'min_hours'              => $this->min_hours,
                'max_hours'              => $this->max_hours,
                'renewal_count'          => $this->renewal_count,
                'renew_time_period_type' => $this->renew_time_period_type,
                'renew_notice'           => $this->renew_notice,
                'payment_method'         => $this->payment_method,
                'payment_period'         => $this->payment_period,
                'role'                   => $this->role ?? '(No role specified)',
                'payment_address'        => $this->payment_address ?? 'No payment address entered',
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
        $attrs['user_sign_status'] = FALSE;
        $attrs['contractor_sign_status'] = FALSE;
        $attrs['schedule_id'] = NULL;
        $attrs['payment_address'] = NULL;
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
