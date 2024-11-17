<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class GithubController extends Controller
{
    public function webhook(Request $request)
    {


        $text = '';
        foreach ($request->commits as $commit) {

            $message = $commit['message'];
            $text .= "- $message";
            if (last($request->commits) !== $commit) {
                $text .= PHP_EOL;
            }
        }

        sendMessage($text, 42);

        return TRUE;

    }
}
