<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:check-users-online')->everyFiveMinutes();
Schedule::command('telescope:prune')->hourly();

