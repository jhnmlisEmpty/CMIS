<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('small_group_leaders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('small_group_id')->constrained('small_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['small_group_id', 'user_id']);
        });

        $now = now();
        DB::table('small_groups')
            ->whereNotNull('leader_id')
            ->orderBy('id')
            ->each(function ($smallGroup) use ($now): void {
                DB::table('small_group_leaders')->insert([
                    'small_group_id' => $smallGroup->id,
                    'user_id' => $smallGroup->leader_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        Schema::table('small_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leader_id');
        });
    }

    public function down(): void
    {
        Schema::table('small_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('leader_id')->nullable()->after('description');
        });

        DB::table('small_groups')->orderBy('id')->each(function ($smallGroup): void {
            $leaderId = DB::table('small_group_leaders')
                ->where('small_group_id', $smallGroup->id)
                ->orderBy('user_id')
                ->value('user_id');

            DB::table('small_groups')->where('id', $smallGroup->id)->update(['leader_id' => $leaderId]);
        });

        Schema::table('small_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('leader_id')->nullable(false)->change();
            $table->foreign('leader_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::dropIfExists('small_group_leaders');
    }
};
