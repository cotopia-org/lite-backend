<?php

namespace App\Http\Controllers;

use App\Enums\AvailabilityType;
use App\Enums\Days;
use App\Enums\Permission;
use App\Http\Requests\ScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Jobs\RecurSchedule;
use App\Models\Calendar;
use App\Models\Contract;
use App\Models\Schedule;
use App\Models\User;
use App\Params\RecurParam;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class   ScheduleController extends Controller {


    public function all() {


        return api(ScheduleResource::collection(Schedule::orderByDesk('id')->get()));

    }

    public function create(Request $request) {

        $types = get_enum_values(AvailabilityType::cases());

        $request->validate([

                               //                               "availability_type" => ["required", Rule::in($types)],
                               'workspace_id' => 'required'
                               //                               "days"              => 'required|array',
                               //                               "days.*"            => Rule::in($days),

                           ]);

        if ($request->contract_id) {

            $contract = Contract::find($request->contract_id);


            $hours = calculateScheduleHours($request->days);
            if ($hours > $contract->max_hours) {
                return error('Schedule hours are more than contract max hours');
            }

            if ($hours * $contract->start_at->weeksInMonth < $contract->min_hours) {
                return error('Schedule hours are less than contract min hours');
            }
        }

        $timezone = $request->timezone ?? 'Asia/Tehran';

        foreach ($request->days as $day) {
            foreach ($day['times'] as $time) {
                $start = str_replace(':', '', $time['start']);
                $end = str_replace(':', '', $time['end']);
                if ($start >= $end) {
                    return error('End time can not be lower than start time');
                }
            }
        }
        $schedule = auth()->user()->schedules()->create([
                                                            'availability_type'   => $request->availability_type ?? 1,
                                                            'days'                => json_encode($request->days, JSON_THROW_ON_ERROR),
                                                            'is_recurrence'       => $request->is_recurrence ?? FALSE,
                                                            'recurrence_start_at' => $request->recurrence_start_at ?? now()->timezone($timezone),
                                                            'recurrence_end_at'   => $request->recurrence_end_at,
                                                            'timezone'            => $timezone,
                                                            'workspace_id'        => $request->workspace_id,
                                                            'contract_id'         => $request->contract_id
                                                        ]);


        return api(ScheduleResource::make($schedule));
    }


    public function update(Request $request, Schedule $schedule) {

        if ($schedule->contract_id !== NULL && $schedule->contract->status() === 'signed') {
            return error('Sorry, you cant update schedule on signed contract');
        }


        if ($request->contract_id) {

            $contract = Contract::find($request->contract_id);


            $hours = calculateScheduleHours($request->days);
            if ($hours > $contract->max_hours) {
                return error('Schedule hours are more than contract max hours');
            }

            if ($hours * $contract->start_at->weeksInMonth < $contract->min_hours) {
                return error('Schedule hours are less than contract min hours');
            }
        }


        $timezone = $request->timezone ?? 'Asia/Tehran';

        $schedule->update([
                              'availability_type'   => $schedule->availability_type,
                              'days'                => json_encode($request->days, JSON_THROW_ON_ERROR),
                              'is_recurrence'       => $request->is_recurrence ?? FALSE,
                              'recurrence_start_at' => $request->recurrence_start_at ?? now()->timezone($timezone),
                              'recurrence_end_at'   => $request->recurrence_end_at,
                              'timezone'            => $timezone,
                              'workspace_id'        => $request->workspace_id,
                              'contract_id'         => $request->contract_id


                          ]);


        if ($schedule->contract_id !== NULL) {
            $schedule->contract->update([
                                            'user_sign_status'       => FALSE,
                                            'contractor_sign_status' => FALSE,
                                        ]);
        }
        return api(ScheduleResource::make($schedule));
    }


    public function delete(Schedule $schedule) {

        if (auth()->user()->isOwner($schedule->user_id)) {
            $schedule->delete();
        }


        return api();
    }
}
