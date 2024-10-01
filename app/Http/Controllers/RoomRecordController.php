<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomRecordRequest;
use App\Http\Resources\RoomRecordResource;
use App\Models\RoomRecord;

class RoomRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return api(RoomRecordResource::make(RoomRecord::all()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoomRecordRequest $request)
    {
        $data = $request->validated();
        $roomRecord = RoomRecord::create($data);

        return api(RoomRecordResource::make($roomRecord));
    }

    /**
     * Display the specified resource.
     */
    public function show(RoomRecord $roomRecord)
    {
        return api(RoomRecordResource::make($roomRecord));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoomRecordRequest $request, RoomRecord $roomRecord)
    {
        $data = $request->validated();
        $roomRecord->update($data);

        return api(RoomRecordRequest::create($roomRecord->fresh()));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoomRecord $roomRecord)
    {
        $roomRecord->delete();
        // TODO - Delete from livekit too

        return api();
    }
}
