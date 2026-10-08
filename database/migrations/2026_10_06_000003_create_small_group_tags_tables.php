<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('normalized_name', 50)->unique();
            $table->timestamps();
        });

        Schema::create('small_group_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('small_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['small_group_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('small_group_tag');
        Schema::dropIfExists('tags');
    }
};
