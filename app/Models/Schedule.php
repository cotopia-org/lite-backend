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

    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }

    protected function availabilityType(): Attribute {
        return Attribute::make(get: fn($value) => json_decode($value),//            set: fn($value) => json_encode($value),
        );
    }

    protected function days(): Attribute {
        return Attribute::make(get: fn($value) => json_decode($value),//            set: fn($value) => json_encode($value),
        );
    }

    //    public function loadExpands(string $relations)
    //    {
    //        $relations = explode(',', $relations);
    //        array_map(fn($r) => in_array($r, ['owner', 'calendar']) && $this->load($r), $relations);
    //
    //        return $this;
    //    }
}
