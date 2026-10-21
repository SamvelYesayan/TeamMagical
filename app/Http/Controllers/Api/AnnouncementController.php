<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Rules\WorkFile;
use App\Support\ActivityLogger;
use App\Support\FileStore;
use App\Support\Notifier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'content' => ['required', 'string', 'max:50000'],
            'status' => ['required', Rule::in([Announcement::DRAFT, Announcement::PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'image' => ['nullable', new WorkFile(imageOnly: true)],
            'attachments' => ['nullable', 'array', 'max:'.config('magical.uploads.max_files')],
            'attachments.*' => [new WorkFile],
        ];
    }

    public function index(Request $request)
    {
        $isAdmin = $request->user()->isAdmin();
        $query = Announcement::query()->with(['author', 'attachments'])->orderByRaw('COALESCE(published_at, created_at) DESC')->orderByDesc('id');

        // Employees only ever receive published announcements whose publication date has arrived.
        if (! $isAdmin || ! $request->boolean('all')) {
            $query->visible();
        }

        return AnnouncementResource::collection($query->paginate((int) $request->input('per_page', 12)));
    }

    public function show(Announcement $announcement): AnnouncementResource
    {
        $this->authorize('view', $announcement);

        return new AnnouncementResource($announcement->load(['author', 'attachments']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $stored = [];

        try {
            $announcement = DB::transaction(function () use ($request, $data, &$stored) {
                $publishedAt = ! empty($data['published_at']) ? Carbon::parse($data['published_at'])->utc() : null;
                if ($data['status'] === Announcement::PUBLISHED && ! $publishedAt) {
                    $publishedAt = now();
                }

                $fields = [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'status' => $data['status'],
                    'published_at' => $publishedAt,
                    'author_id' => $request->user()->id,
                ];

                if ($image = $request->file('image')) {
                    $info = FileStore::store($image, 'announcements');
                    $stored[] = $info['path'];
                    $fields['image_path'] = $info['path'];
                    $fields['image_mime'] = $info['mime'];
                }

                $announcement = Announcement::query()->create($fields);

                foreach ($request->file('attachments', []) as $file) {
                    $info = FileStore::store($file, 'announcements');
                    $stored[] = $info['path'];
                    $announcement->attachments()->create($info);
                }

                return $announcement;
            });
        } catch (\Throwable $e) {
            FileStore::deleteMany($stored);
            throw $e;
        }

        ActivityLogger::log($request->user(), 'announcement.created', $announcement, "Ստեղծվեց հայտարարություն՝ {$announcement->title}");
        if ($announcement->status === Announcement::PUBLISHED) {
            ActivityLogger::log($request->user(), 'announcement.published', $announcement, "Հրապարակվեց՝ {$announcement->title}");
            Notifier::announcement($announcement);
        }

        return (new AnnouncementResource($announcement->load(['author', 'attachments'])))->response()->setStatusCode(201);
    }

    /** Sent as POST because multipart bodies cannot be PUT reliably across PHP setups. */
    public function update(Request $request, Announcement $announcement): AnnouncementResource
    {
        $rules = $this->rules() + [
            'remove_image' => ['nullable', 'boolean'],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer'],
        ];
        $data = $request->validate($rules);

        $stored = [];
        $toDelete = [];
        $wasPublished = $announcement->status === Announcement::PUBLISHED;

        try {
            DB::transaction(function () use ($request, $data, $announcement, &$stored, &$toDelete) {
                $publishedAt = ! empty($data['published_at']) ? Carbon::parse($data['published_at'])->utc() : $announcement->published_at;
                if ($data['status'] === Announcement::PUBLISHED && ! $publishedAt) {
                    $publishedAt = now();
                }

                $fields = [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'status' => $data['status'],
                    'published_at' => array_key_exists('published_at', $data) && empty($data['published_at']) && $data['status'] === Announcement::DRAFT ? null : $publishedAt,
                ];

                if ($request->boolean('remove_image') && $announcement->image_path) {
                    $toDelete[] = $announcement->image_path;
                    $fields['image_path'] = null;
                    $fields['image_mime'] = null;
                }

                if ($image = $request->file('image')) {
                    if ($announcement->image_path) {
                        $toDelete[] = $announcement->image_path;
                    }
                    $info = FileStore::store($image, 'announcements');
                    $stored[] = $info['path'];
                    $fields['image_path'] = $info['path'];
                    $fields['image_mime'] = $info['mime'];
                }

                $announcement->update($fields);

                $removeIds = $data['remove_attachments'] ?? [];
                if ($removeIds) {
                    $announcement->attachments()->whereIn('id', $removeIds)->get()->each(function (AnnouncementAttachment $a) use (&$toDelete) {
                        $toDelete[] = $a->path;
                        $a->delete();
                    });
                }

                foreach ($request->file('attachments', []) as $file) {
                    $info = FileStore::store($file, 'announcements');
                    $stored[] = $info['path'];
                    $announcement->attachments()->create($info);
                }
            });
        } catch (\Throwable $e) {
            FileStore::deleteMany($stored);
            throw $e;
        }

        FileStore::deleteMany($toDelete);

        $announcement->refresh();
        ActivityLogger::log($request->user(), 'announcement.updated', $announcement, "Խմբագրվեց հայտարարություն՝ {$announcement->title}");
        if (! $wasPublished && $announcement->status === Announcement::PUBLISHED) {
            ActivityLogger::log($request->user(), 'announcement.published', $announcement, "Հրապարակվեց՝ {$announcement->title}");
        }
        Notifier::announcement($announcement);

        return new AnnouncementResource($announcement->load(['author', 'attachments']));
    }

    public function publish(Request $request, Announcement $announcement): AnnouncementResource
    {
        $announcement->update([
            'status' => Announcement::PUBLISHED,
            'published_at' => $announcement->published_at ?? now(),
        ]);

        ActivityLogger::log($request->user(), 'announcement.published', $announcement, "Հրապարակվեց՝ {$announcement->title}");
        Notifier::announcement($announcement);

        return new AnnouncementResource($announcement->load(['author', 'attachments']));
    }

    public function unpublish(Request $request, Announcement $announcement): AnnouncementResource
    {
        $announcement->update(['status' => Announcement::DRAFT]);
        ActivityLogger::log($request->user(), 'announcement.unpublished', $announcement, "Հանվեց հրապարակումից՝ {$announcement->title}");

        return new AnnouncementResource($announcement->load(['author', 'attachments']));
    }

    public function destroy(Request $request, Announcement $announcement): JsonResponse
    {
        $title = $announcement->title;
        $paths = $announcement->attachments()->pluck('path')->all();
        if ($announcement->image_path) {
            $paths[] = $announcement->image_path;
        }

        $announcement->delete();
        FileStore::deleteMany($paths);

        ActivityLogger::log($request->user(), 'announcement.deleted', null, "Ջնջվեց հայտարարություն՝ {$title}");

        return response()->json(['message' => 'Հայտարարությունը ջնջվեց։']);
    }
}
