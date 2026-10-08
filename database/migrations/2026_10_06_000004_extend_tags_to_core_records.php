<?php

use App\Models\SmallGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->string('type', 30)->default('small_group')->after('id');
            $table->dropUnique(['normalized_name']);
            $table->unique(['type', 'normalized_name']);
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->timestamps();

            $table->unique(['tag_id', 'taggable_type', 'taggable_id']);
        });

        DB::table('small_group_tag')->orderBy('id')->each(function ($assignment): void {
            DB::table('taggables')->insert([
                'tag_id' => $assignment->tag_id,
                'taggable_type' => SmallGroup::class,
                'taggable_id' => $assignment->small_group_id,
                'created_at' => $assignment->created_at,
                'updated_at' => $assignment->updated_at,
            ]);
        });

        Schema::dropIfExists('small_group_tag');
    }

    public function down(): void
    {
        Schema::create('small_group_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('small_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['small_group_id', 'tag_id']);
        });

        DB::table('taggables')
            ->join('tags', 'tags.id', '=', 'taggables.tag_id')
            ->where('tags.type', 'small_group')
            ->where('taggables.taggable_type', SmallGroup::class)
            ->select('taggables.*')
            ->orderBy('taggables.id')
            ->each(function ($assignment): void {
                DB::table('small_group_tag')->insert([
                    'small_group_id' => $assignment->taggable_id,
                    'tag_id' => $assignment->tag_id,
                    'created_at' => $assignment->created_at,
                    'updated_at' => $assignment->updated_at,
                ]);
            });

        Schema::dropIfExists('taggables');
        DB::table('tags')->where('type', '!=', 'small_group')->delete();

        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['type', 'normalized_name']);
            $table->dropColumn('type');
            $table->unique('normalized_name');
        });
    }
};
