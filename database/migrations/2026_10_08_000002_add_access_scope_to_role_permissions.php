<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->string('access_scope', 20)->default('all')->after('permission');
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->dropColumn('access_scope');
        });
    }
};
