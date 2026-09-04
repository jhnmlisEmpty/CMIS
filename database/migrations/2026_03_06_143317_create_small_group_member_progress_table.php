<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('small_group_member_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('small_group_member_id')->constrained('small_group_members')->cascadeOnDelete();
            $table->foreignId('small_group_lesson_id')->constrained('small_group_lessons')->cascadeOnDelete();
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['small_group_member_id', 'small_group_lesson_id'], 'member_lesson_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('small_group_member_progress');
    }
};
