<?php

namespace App\Http\Controllers;

use App\Enums\AvailabilityType;
use App\Enums\Days;
use App\Enums\Permission;
use App\Http\Requests\ScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Jobs\RecurSchedule;
use App\Models\Calendar;
use App\Models\Schedule;
use App\Models\User;
use App\Params\RecurParam;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{


    public function all()
    {


        return api(ScheduleResource::collection(Schedule::orderByDesk('id')->get()));

    }

    public function create(Request $request)
    {

        $types = get_enum_values(AvailabilityType::cases());
        $days = get_enum_values(Days::cases());

        $request->validate([

                               "availability_type" => ["required", Rule::in($types)],
                               "days"              => 'required|array',
                               "days.*"            => Rule::in($days),

                           ]);

        $timezone = $request->timezone ?? 'Asia/Tehran';

        $schedule = auth()->user()->schedules()->create([
                                                            'availability_type'   => $request->availability_type,
                                                            'days'                => json_encode($request->days,
                                                                                                 JSON_THROW_ON_ERROR),
                                                            'start_time'          => $request->start_time ?? '08:00:00',
                                                            'end_time'            => $request->end_time ?? '18:00:00',
                                                            'is_recurrence'       => $request->is_recurrence ?? FALSE,
                                                            'recurrence_start_at' => $request->recurrence_start_at ?? now()->timezone($timezone),
                                                            'recurrence_end_at'   => $request->recurrence_end_at,
                                                            'timezone'            => $timezone,
                                                        ]);


        return api(ScheduleResource::make($schedule));
    }


    public function update(Request $request, Schedule $schedule)
    {

        if (auth()->user()->isOwner($schedule->user_id)) {
            $schedule->update($request->all());
        }


        return api(ScheduleResource::make($schedule));
    }


    public function delete(Schedule $schedule)
    {

        if (auth()->user()->isOwner($schedule->user_id)) {
            $schedule->delete();
        }


        return api();
    }
}
