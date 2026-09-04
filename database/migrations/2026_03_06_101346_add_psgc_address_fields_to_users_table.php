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
        Schema::table('users', function (Blueprint $table) {
            $table->string('region_code', 20)->nullable()->after('address');
            $table->string('province_code', 20)->nullable()->after('region_code');
            $table->string('city_code', 20)->nullable()->after('province_code');
            $table->string('barangay_code', 20)->nullable()->after('city_code');
            $table->string('street_address', 255)->nullable()->after('barangay_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'region_code',
                'province_code',
                'city_code',
                'barangay_code',
                'street_address',
            ]);
        });
    }
};
