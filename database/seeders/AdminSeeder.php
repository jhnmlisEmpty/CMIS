<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Create the default administrator account when it does not exist.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@cmis.com'],
            [
                'uuid' => Str::uuid()->toString(),
                'name' => 'Admin User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'gender' => User::GENDER_MALE,
                'birthdate' => '2001-01-01',
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
            ],
        );

        $admin->update(['birthdate' => '2001-01-01']);
    }
}
