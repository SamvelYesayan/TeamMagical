<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class Notifier
{
    /**
     * Create in-app notifications. Inactive users and the acting user ($except) are skipped.
     *
     * @param  User|iterable<User>|null  $users
     */
    public static function send(
        User|iterable|null $users,
        string $type,
        string $title,
        ?string $body = null,
        ?string $link = null,
        array $data = [],
        ?User $except = null,
    ): void {
        $list = collect($users instanceof User ? [$users] : ($users ?? []))
            ->filter(fn ($u) => $u instanceof User && $u->isActive())
            ->reject(fn (User $u) => $except && $u->id === $except->id)
            ->unique('id');

        foreach ($list as $user) {
            AppNotification::query()->create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body ? mb_strimwidth($body, 0, 500, '…') : null,
                'link' => $link,
                'data' => $data ?: null,
            ]);
        }
    }

    /** @return Collection<int, User> */
    public static function admins(): Collection
    {
        return User::query()->active()->admins()->get();
    }

    /** Tell every active user (except the author) that an announcement is now visible. */
    public static function announcement(Announcement $announcement): void
    {
        if ($announcement->notified_at !== null || ! $announcement->isVisible()) {
            return;
        }

        $author = $announcement->author_id ? User::query()->find($announcement->author_id) : null;

        User::query()->active()->get()->each(function (User $user) use ($announcement, $author) {
            if ($author && $user->id === $author->id) {
                return;
            }
            AppNotification::query()->create([
                'user_id' => $user->id,
                'type' => 'announcement.published',
                'title' => 'Նոր հայտարարություն',
                'body' => $announcement->title,
                'link' => '/announcements',
                'data' => ['announcement_id' => $announcement->id],
            ]);
        });

        $announcement->forceFill(['notified_at' => now()])->save();
    }
}
