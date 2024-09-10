<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportResource;
use App\Models\Message;
use App\Models\Room;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function create(Request $request)
    {

        $request->validate([
                               'title',
                               'description',
                               'type',
                           ]);

        $user = auth()->user();
        $models = [
            NULL        => NULL,
            'user'      => User::class,
            'room'      => Room::class,
            'workspace' => Workspace::class,
            'message'   => Message::class,
        ];
        $report = $user->reports()->create([
                                               'title'           => $request->title,
                                               'description'     => $request->descritpion,
                                               'type'            => $request->type,
                                               'reportable_type' => $models($request->model_type),
                                               'reportable_id'   => $request->model_id,
                                           ]);
        if ($request->get('files')) {
            foreach ($request->get('files') as $file) {
                File::syncFile($file, $report);

            }
        }

        return api(ReportResource::make($report));

    }
}
