<?php

namespace App\Models;

use App\Utilities\Constants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Schedule extends Model {
    use HasFactory;

    protected $fillable = [
        'availability_type',
        'user_id',
        'contract_id',
        'days',
        'is_recurrence',
        'recurrence_start_at',
        'recurrence_end_at',
        'timezone',
        'workspace_id',
    ];

    //    protected $casts = [
    //        'starts_at' => 'datetime:' . Constants::SCHEDULE_DATE_FORMAT,
    //        'ends_at' => 'datetime:' . Constants::SCHEDULE_DATE_FORMAT,
    //    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function contract() {
        return $this->belongsTo(Contract::class);
    }


    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }

    protected function availabilityType(): Attribute {
        return Attribute::make(get: fn($value) => json_decode($value),//            set: fn($value) => json_encode($value),
        );
    }


    public function hours($days = NULL) {
        $hours = 0;
        if ($days === NULL) {
            $days = $this->days;
        }
        foreach ($days as $day) {
            foreach ($day->times as $time) {
                $end = now()->setTimeFromTimeString($time->end);
                $start = now()->setTimeFromTimeString($time->start);


                $hours += $start->diffInHours($end);
            }
        }

        return $hours;
    }


    protected function days(): Attribute {
        return Attribute::make(get: fn($value) => json_decode($value),//            set: fn($value) => json_encode($value),
        );
    }


}
