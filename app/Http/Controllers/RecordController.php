<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\EgressServiceClient;
use App\Enums\RecordStatus;
use App\Http\Requests\RecordRequest;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Livekit\EncodedFileOutput;
use Livekit\StreamOutput;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RecordController extends Controller
{
    public function __construct(
        private EgressServiceClient $egressSrv
    )
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return api(RecordResource::make(Record::all()));
    }

    /**
     * Display the specified resource.
     */
    public function show(Record $record): Response|Application|ResponseFactory
    {
        return api(RecordResource::make($record));
    }

    public function start(RecordRequest $request): Response|Application|ResponseFactory
    {
        $data = $request->validated();
        $layout = config('livekit.egressLayout');
        /** @var EncodedFileOutput $output */
        $output = app(StreamOutput::class);
        $res = $this->egressSrv->startRoomCompositeEgress(roomName: $data['name'], layout: $layout, output: $output, audioOnly: ! $data['is_video'], videoOnly: ! $data['is_audio']);

        $data['egress_id'] = $res->getEgressId();
        $data['room_id'] = $res->getRoomId();
        $data['started_at'] = now();
        $data['status'] = RecordStatus::IN_PROGRESS->value;
        $record = Record::create($data);

        return api(data: RecordResource::make($record), http_code: Response::HTTP_CREATED);
    }

    public function stop(Record $record): Response|Application|ResponseFactory
    {
        $res = $this->egressSrv->stopEgress($record->egress_id);
        if (! empty($err = $res->getError())) {

            throw new BadRequestHttpException($err);
        }

        $res->getFileResults();

        $record->update([
            'ended_at' => now(),
            'status'   => RecordStatus::DONE->value,
        ]);

        return api();
    }
}
