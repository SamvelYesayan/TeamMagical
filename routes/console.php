<?php

use Illuminate\Support\Facades\Schedule;

// Reminders, overdue flags and scheduled announcements. Requires the Laravel scheduler (see README).
Schedule::command('magical:process')->everyMinute()->withoutOverlapping();
