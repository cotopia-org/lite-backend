<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class GithubController extends Controller
{
    public function webhook(Request $request)
    {


        $pusher = $request->pusher['name'] . '(' . $request->pusher['email'] . ')';
        $repo = $request->repository['full_name'];

        $text = "A new commit pushed to $repo by $pusher

Commits:";
        foreach ($request->commits as $commit) {

            $message = $commit['message'];
            $by = $commit['author']['name'];
            $created_at = Carbon::create($commit['timestamp'])->diffForHumans();

            $text .= "-$message , by $by at $created_at" . PHP_EOL;
        }

        sendMessage($text, 42);

        return TRUE;

    }
}
