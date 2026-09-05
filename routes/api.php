<?php

use App\Http\Controllers\Api\BulkMemberRegistrationController;
use App\Http\Controllers\Api\MemberRegistrationController;
use App\Http\Controllers\Api\PhilippineAddressController;
use Illuminate\Support\Facades\Route;

Route::post('/member-registrations', [MemberRegistrationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.member-registrations.store');

Route::post('/member-registrations/bulk', [BulkMemberRegistrationController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('api.member-registrations.bulk.store');

Route::prefix('addresses')->middleware('throttle:60,1')->group(function () {
    Route::get('/regions', [PhilippineAddressController::class, 'regions']);
    Route::get('/provinces/{regionCode}', [PhilippineAddressController::class, 'provinces'])->whereAlphaNumeric('regionCode');
    Route::get('/cities/{provinceCode}', [PhilippineAddressController::class, 'cities'])->whereAlphaNumeric('provinceCode');
    Route::get('/barangays/{cityCode}', [PhilippineAddressController::class, 'barangays'])->whereAlphaNumeric('cityCode');
});
