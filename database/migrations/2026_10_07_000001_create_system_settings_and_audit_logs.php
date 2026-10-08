<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('True Vine World Harvest Church - Pangasinan');
            $table->string('short_name')->default('TVWHC Pangasinan');
            $table->string('logo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('timezone', 50)->default('Asia/Manila');
            $table->string('locale', 10)->default('en');
            $table->string('date_format', 20)->default('M j, Y');
            $table->timestamps();
        });

        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('attendance_required')->default(false);
            $table->unsignedTinyInteger('reward_points')->default(10);
            $table->unsignedTinyInteger('penalty_points')->default(10);
            $table->string('audience_mode', 20)->default('selected');
            $table->json('role_audience')->nullable();
            $table->json('small_group_audience')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40)->index();
            $table->string('module', 60)->index();
            $table->nullableMorphs('auditable');
            $table->string('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        DB::table('church_settings')->insert([
            'name' => 'True Vine World Harvest Church - Pangasinan',
            'short_name' => 'TVWHC Pangasinan',
            'timezone' => 'Asia/Manila',
            'locale' => 'en',
            'date_format' => 'M j, Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('attendance_settings')->insert([
            'attendance_required' => false,
            'reward_points' => 10,
            'penalty_points' => 10,
            'audience_mode' => 'selected',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('attendance_settings');
        Schema::dropIfExists('church_settings');
    }
};
