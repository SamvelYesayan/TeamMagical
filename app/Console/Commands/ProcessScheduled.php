<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Deadline;
use App\Models\Setting;
use App\Support\ActivityLogger;
use App\Support\Fmt;
use App\Support\Notifier;
use Illuminate\Console\Command;

class ProcessScheduled extends Command
{
    protected $signature = 'magical:process';

    protected $description = 'Send deadline reminders, flag overdue deadlines and announce scheduled announcements';

    public function handle(): int
    {
        $reminders = $this->sendReminders();
        $overdue = $this->flagOverdue();
        $announced = $this->publishScheduledAnnouncements();

        $this->info("Reminders: {$reminders}, overdue: {$overdue}, announcements: {$announced}");

        return self::SUCCESS;
    }

    private function sendReminders(): int
    {
        $hours = max(1, (int) Setting::get('reminder_hours'));
        $count = 0;

        Deadline::query()->with(['assignee'])->active()
            ->whereNull('reminder_sent_at')
            ->where('due_at', '>', now())
            ->where('due_at', '<=', now()->addHours($hours))
            ->chunkById(100, function ($deadlines) use (&$count) {
                foreach ($deadlines as $d) {
                    Notifier::send(
                        $d->assignee,
                        'deadline.approaching',
                        'Դեդլայնը մոտենում է',
                        "«{$d->title}»՝ ժամկետ ".Fmt::dt($d->due_at),
                        "/deadlines/{$d->id}",
                        ['deadline_id' => $d->id],
                    );
                    $d->forceFill(['reminder_sent_at' => now()])->save();
                    $count++;
                }
            });

        return $count;
    }

    private function flagOverdue(): int
    {
        $count = 0;

        Deadline::query()->with(['assignee'])->overdue()
            ->whereNull('overdue_notified_at')
            ->chunkById(100, function ($deadlines) use (&$count) {
                foreach ($deadlines as $d) {
                    $body = "«{$d->title}»՝ ժամկետն էր ".Fmt::dt($d->due_at);
                    Notifier::send($d->assignee, 'deadline.overdue', 'Դեդլայնը ժամկետանց է', $body, "/deadlines/{$d->id}", ['deadline_id' => $d->id]);
                    Notifier::send(Notifier::admins(), 'deadline.overdue', 'Ժամկետանց դեդլայն', "{$d->assignee?->name}՝ {$body}", "/deadlines/{$d->id}", ['deadline_id' => $d->id]);
                    ActivityLogger::log(null, 'deadline.overdue', $d, "Ժամկետանց դարձավ՝ {$d->title}");
                    $d->forceFill(['overdue_notified_at' => now()])->save();
                    $count++;
                }
            });

        return $count;
    }

    private function publishScheduledAnnouncements(): int
    {
        $count = 0;

        Announcement::query()->visible()->whereNull('notified_at')->get()->each(function (Announcement $a) use (&$count) {
            Notifier::announcement($a);
            $count++;
        });

        return $count;
    }
}
