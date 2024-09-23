<?php

use Illuminate\Support\Facades\Schedule;

//Schedule::command('app:check-users-online')
//        ->everyFiveMinutes(); // Check if users are in livekit, but they haven't joined to room, join them

Schedule::command('app:check-users-in-socket-command')
        ->everyMinute(); // Check if online users aren't in socket, make them offline


Schedule::command('telescope:prune')->hourly();
Schedule::command('app:check-talks-command')->everyMinute();

