<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('attendance_required')->default(false)->after('event_type')->index();
            $table->unsignedTinyInteger('attendance_reward_points')->default(10)->after('attendance_required');
            $table->unsignedTinyInteger('absence_penalty_points')->default(10)->after('attendance_reward_points');
            $table->timestamp('audience_snapshotted_at')->nullable()->after('absence_penalty_points');
            $table->timestamp('attendance_finalized_at')->nullable()->after('audience_snapshotted_at');
            $table->index(['event_date', 'attendance_finalized_at']);
        });

        Schema::create('event_audience_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->enum('audience_type', ['all_active', 'role', 'small_group', 'user']);
            $table->string('audience_key')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'audience_type', 'audience_key'], 'event_audience_rule_unique');
        });

        Schema::create('event_attendance_expectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'present', 'absent'])->default('pending');
            $table->unsignedTinyInteger('reward_points')->default(10);
            $table->unsignedTinyInteger('penalty_points')->default(10);
            $table->smallInteger('points_delta')->nullable();
            $table->unsignedTinyInteger('score_after')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('event_attendance_expectation_groups', function (Blueprint $table) {
            $table->foreignId('expectation_id')->constrained('event_attendance_expectations')->cascadeOnDelete();
            $table->foreignId('small_group_id')->constrained()->cascadeOnDelete();
            $table->primary(['expectation_id', 'small_group_id']);
        });

        Schema::create('member_engagement_stats', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('current_score')->default(100);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->unsignedInteger('required_events')->default(0);
            $table->unsignedInteger('attended_events')->default(0);
            $table->unsignedInteger('missed_events')->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('analytics_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('low_score_threshold')->default(60);
            $table->unsignedTinyInteger('low_attendance_threshold')->default(60);
            $table->unsignedSmallInteger('rolling_days')->default(90);
            $table->unsignedTinyInteger('minimum_required_events')->default(3);
            $table->timestamps();
        });

        DB::table('analytics_settings')->insert([
            'low_score_threshold' => 60,
            'low_attendance_threshold' => 60,
            'rolling_days' => 90,
            'minimum_required_events' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_settings');
        Schema::dropIfExists('member_engagement_stats');
        Schema::dropIfExists('event_attendance_expectation_groups');
        Schema::dropIfExists('event_attendance_expectations');
        Schema::dropIfExists('event_audience_rules');

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['event_date', 'attendance_finalized_at']);
            $table->dropIndex(['attendance_required']);
            $table->dropColumn([
                'attendance_required', 'attendance_reward_points', 'absence_penalty_points',
                'audience_snapshotted_at', 'attendance_finalized_at',
            ]);
        });
    }
};
