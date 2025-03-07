<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('app:check-users-in-socket-command')
        ->everyMinute(); // Check if online users aren't in socket, make them offline


Schedule::command('telescope:prune')->hourly();
Schedule::command('app:check-talks-command')->everyMinute();
Schedule::command('app:check-in-schedule-command')->everyMinute(); // Check are users in their schedule or not

