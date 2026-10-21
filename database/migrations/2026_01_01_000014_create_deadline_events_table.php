<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deadline_id')->constrained('deadlines')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->json('data')->nullable();
            $table->dateTime('created_at');

            $table->index(['deadline_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_events');
    }
};
