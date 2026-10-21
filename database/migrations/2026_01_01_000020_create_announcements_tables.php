<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->longText('content');
            $table->string('image_path', 500)->nullable();
            $table->string('image_mime', 100)->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->dateTime('published_at')->nullable()->index();
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('announcement_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('path', 500);
            $table->string('mime', 150)->nullable();
            $table->unsignedBigInteger('size');
            $table->string('kind', 10)->default('file');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_attachments');
        Schema::dropIfExists('announcements');
    }
};
