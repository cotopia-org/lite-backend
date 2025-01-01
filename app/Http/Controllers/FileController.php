<?php

namespace App\Http\Controllers;

use App\Http\Resources\FileResource;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller {
    public function upload(Request $request) {
        $request->validate([
                               //                               'file' => 'required|mimes:png,jpg,jpeg,webp' // TODO: changed for test
                               'file' => 'mimes:png,jpg,jpeg,webp,mp3,wav,webm', // TODO: changed for test
                           ]);

        if ($request->hasFile('file')) {
            $path = Storage::disk('public')->put('images', $request->file);
            $mime = $request->file('file')->getClientMimeType();

        } else {
            $path = $request->path;
            $mime = NULL;
        }
        $file = File::create([
                                 'path'      => $path,
                                 'type'      => $request->type,
                                 'mime_type' => $mime,
                             ]);

        return api(FileResource::make($file));

    }

    public function delete(File $file) {

        if (Storage::disk('public')->exists($file->path)) {
            Storage::disk('public')->delete($file->path);
        }
        $file->delete();

        return api(TRUE);

    }

    public function all() {
        return api(FileResource::collection(File::all()));
    }
}
