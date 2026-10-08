<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('roles')->insert([
            ['name' => 'Admin', 'slug' => 'admin', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pastor', 'slug' => 'pastor', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ministry Head', 'slug' => 'ministry_head', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Small Group Leader', 'slug' => 'small_group_leader', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Member', 'slug' => 'member', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['role_id', 'user_id']);
            $table->index(['user_id', 'role_id']);
        });

        $roleIds = DB::table('roles')->pluck('id', 'slug');
        DB::table('users')->orderBy('id')->get(['id', 'role'])->each(function ($user) use ($roleIds, $now): void {
            $roleId = $roleIds[$user->role] ?? $roleIds['member'];
            DB::table('role_user')->insert([
                'role_id' => $roleId,
                'user_id' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        Schema::create('role_permissions_new', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('permission', 100);
            $table->unique(['role_id', 'permission']);
        });

        DB::table('role_permissions')->orderBy('id')->get()->each(function ($permission) use ($roleIds): void {
            if (isset($roleIds[$permission->role])) {
                DB::table('role_permissions_new')->insert([
                    'role_id' => $roleIds[$permission->role],
                    'permission' => $permission->permission,
                ]);
            }
        });

        Schema::drop('role_permissions');
        Schema::rename('role_permissions_new', 'role_permissions');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 80)->default('member')->after('longitude');
        });

        DB::table('users')->orderBy('id')->get(['id'])->each(function ($user): void {
            $slug = DB::table('role_user')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('role_user.user_id', $user->id)
                ->orderBy('roles.id')
                ->value('roles.slug') ?? 'member';
            DB::table('users')->where('id', $user->id)->update(['role' => $slug]);
        });

        Schema::create('role_permissions_old', function (Blueprint $table): void {
            $table->id();
            $table->string('role', 80);
            $table->string('permission', 100);
            $table->unique(['role', 'permission']);
            $table->index('role');
        });

        DB::table('role_permissions')
            ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
            ->orderBy('role_permissions.id')
            ->get(['roles.slug', 'role_permissions.permission'])
            ->each(fn ($permission) => DB::table('role_permissions_old')->insert([
                'role' => $permission->slug,
                'permission' => $permission->permission,
            ]));

        Schema::drop('role_permissions');
        Schema::rename('role_permissions_old', 'role_permissions');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
