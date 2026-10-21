<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deadline_id')->constrained('deadlines')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('previous_due_at');
            $table->dateTime('proposed_due_at');
            $table->text('reason');
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('status_before', 30);
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_comment')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();

            $table->index(['deadline_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_change_requests');
    }
};
