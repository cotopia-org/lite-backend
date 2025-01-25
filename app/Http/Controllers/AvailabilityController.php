<?php

namespace App\Http\Controllers;

use App\Http\Resources\AvailabilityResource;
use App\Models\Availability;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AvailabilityController extends Controller {
    public function create(Request $request) {
        $user = auth()->user();
        $request->validate([
                               'start_at'     => 'required',
                               'workspace_id' => 'required',
                               'end_at'       => 'required',
                               'timezone'     => 'required',
                           ]);


        $availability = Availability::create([
                                                 'type'         => $request->type,
                                                 'user_id'      => $user->id,
                                                 'contract_id'  => $request->contract_id,
                                                 'workspace_id' => $request->workspace_id,
                                                 'start_at'     => Carbon::parse($request->start_at),
                                                 'end_at'       => Carbon::parse($request->end_at),
                                                 'timezone'     => $request->timezone,
                                                 'title'        => $request->title,
                                             ]);


        return api(AvailabilityResource::make($availability));
    }


    public function update(Availability $availability, Request $request) {

    }
}
