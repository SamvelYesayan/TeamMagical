<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deadline_id')->constrained('deadlines')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
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
        Schema::dropIfExists('deadline_attachments');
    }
};
