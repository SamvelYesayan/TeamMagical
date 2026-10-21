<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\DeadlineAttachment;
use App\Models\SubmissionFile;
use App\Support\FileStore;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The ONLY way to read an uploaded file. Files are stored outside the web root on a private disk,
 * so every download passes through an authorisation check first.
 */
class FileController extends Controller
{
    public function deadlineAttachment(DeadlineAttachment $attachment): Response
    {
        $this->authorize('view', $attachment->deadline);

        return FileStore::response($attachment->path, $attachment->original_name, $attachment->mime, inlineImage: false);
    }

    /** Submitted work: administrators and the submitting assignee only. */
    public function submissionFile(SubmissionFile $file): Response
    {
        $this->authorize('view', $file->submission);

        return FileStore::response($file->path, $file->original_name, $file->mime, inlineImage: $file->kind === 'image' && request()->boolean('inline'));
    }

    public function announcementImage(Announcement $announcement): Response
    {
        $this->authorize('view', $announcement);
        abort_if($announcement->image_path === null, 404);

        return FileStore::response($announcement->image_path, 'announcement-'.$announcement->id, $announcement->image_mime, inlineImage: true);
    }

    public function announcementAttachment(AnnouncementAttachment $attachment): Response
    {
        $this->authorize('view', $attachment->announcement);

        return FileStore::response($attachment->path, $attachment->original_name, $attachment->mime, inlineImage: false);
    }
}
