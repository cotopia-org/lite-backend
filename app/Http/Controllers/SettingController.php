<?php

namespace App\Http\Controllers;

use App\Http\Resources\SettingResource;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;

class SettingController extends Controller {

    public function set(Request $request) {
        $request->validate([
                               'key'   => 'required',
                               'value' => 'required',
                               //                               'model_type' => 'required',
                               //                               'model_id'   => 'required',
                           ]);


        //        $models = [
        //            'room'      => Room::class,
        //            'workspace' => Workspace::class,
        //            'user'      => User::class,
        //        ];
        //
        //
        //
        //
        //        $model = (new $models[$request->model_type])->find($request->model_id);

        $model = auth()->user();
        $setting = $model->settings()->where('key', $request->key)->first();
        if ($setting === NULL) {
            $setting = $model->settings()->create([
                                                      'key'   => $request->key,
                                                      'value' => $request->value,
                                                  ]);

        } else {
            $setting->update([
                                 'value' => $request->value,
                             ]);
        }


        return api(SettingResource::make($setting));

    }


}

