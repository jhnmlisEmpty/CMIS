<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The legacy records are intentionally discarded: lessons are now one
        // shared curriculum for the whole church.
        Schema::dropIfExists('small_group_member_progress');
        Schema::dropIfExists('small_group_lessons');

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('order')->default(1);
            $table->longText('content')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
            $table->index(['status', 'order']);
        });

        Schema::create('small_group_member_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('small_group_member_id')->constrained('small_group_members')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['small_group_member_id', 'lesson_id'], 'member_lesson_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('small_group_member_progress');
        Schema::dropIfExists('lessons');
    }
};
