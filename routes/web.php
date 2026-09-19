<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MemberListExportController;
use App\Livewire\Attendance\CreateEvent;
use App\Livewire\Attendance\IndexEvent;
use App\Livewire\Attendance\UpdateEvent;
use App\Livewire\Attendance\ViewEvent;
use App\Livewire\Analytics\AnalyticsDashboard;
use App\Livewire\Settings\ManageAccessControl;
use App\Livewire\Lessons\ManageLessons as GlobalManageLessons;
use App\Livewire\Lessons\ViewLesson as GlobalViewLesson;
use App\Livewire\SmallGroup\CreateSmallGroup;
use App\Livewire\SmallGroup\IndexSmallGroup;
use App\Livewire\SmallGroup\ManageMembers;
use App\Livewire\SmallGroup\UpdateSmallGroup;
use App\Livewire\SmallGroup\ViewSmallGroup;
use App\Livewire\User\CreateUser;
use App\Livewire\User\EditProfile;
use App\Livewire\User\IndexUser;
use App\Livewire\User\UpdateUser;
use App\Livewire\User\UserLocationsMap;
use App\Livewire\User\ViewUser;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/profile-photos/{filename}', function (string $filename) {
        abort_if($filename === '' || basename($filename) !== $filename, 404);

        $path = 'profile-photos/'.$filename;
        abort_unless(Storage::disk('public')->exists($path), 404);
        $owner = \App\Models\User::where('profile_photo_path', $path)->firstOrFail();
        abort_unless(auth()->user()->canAccessMember($owner), 403);

        return response()->file(Storage::disk('public')->path($path));
    })->where('filename', '[^/]+')->name('profile-photo');

    Route::get('/small-group-photos/{filename}', function (string $filename) {
        abort_if($filename === '' || basename($filename) !== $filename, 404);

        $path = 'small-group-photos/'.$filename;
        abort_unless(Storage::disk('public')->exists($path), 404);
        $smallGroup = \App\Models\SmallGroup::where('photo_path', $path)->firstOrFail();
        abort_unless(auth()->user()->canAccessSmallGroup($smallGroup), 403);

        return response()->file(Storage::disk('public')->path($path));
    })->where('filename', '[^/]+')->name('small-group-photo');

    Route::get('/', function () {
        return view('home');
    })->middleware('can:dashboard.view')->name('home');
    Route::get('/analytics', AnalyticsDashboard::class)->middleware('can:analytics.view')->name('analytics.index');

    Route::get('/profile', ViewUser::class)->name('profile');
    Route::get('/profile/edit', EditProfile::class)->name('profile.edit');

    // User/Member Management
    Route::get('/users', IndexUser::class)->middleware('can:users.view')->name('users.index');
    Route::get('/users/export', MemberListExportController::class)->middleware('can:users.export')->name('users.export');
    Route::get('/users/map', UserLocationsMap::class)->middleware('can:users.map')->name('users.map');
    Route::get('/users/create', CreateUser::class)->middleware('can:users.create')->name('users.create');
    Route::get('/users/{user}', ViewUser::class)->name('users.show');
    Route::get('/users/{user}/edit', UpdateUser::class)->middleware('can:users.update')->name('users.edit');

    // Small Group Management
    Route::get('/small-groups', IndexSmallGroup::class)->middleware('can:small_groups.view')->name('small-groups.index');
    Route::get('/small-groups/create', CreateSmallGroup::class)->middleware('can:small_groups.create')->name('small-groups.create');
    Route::get('/small-groups/{smallGroup}', ViewSmallGroup::class)->middleware('can:small_groups.view')->name('small-groups.show');
    Route::get('/small-groups/{smallGroup}/edit', UpdateSmallGroup::class)->middleware('can:small_groups.update')->name('small-groups.edit');
    Route::get('/small-groups/{smallGroup}/members', ManageMembers::class)->middleware('can:group_members.view')->name('small-groups.members');

    // Shared lesson curriculum
    Route::get('/lessons', GlobalManageLessons::class)->middleware('can:lessons.view')->name('lessons.index');
    Route::get('/lessons/{lesson}', GlobalViewLesson::class)->middleware('can:lessons.view')->name('lessons.show');

    // Event Management
    Route::get('/events', IndexEvent::class)->middleware('can:events.view')->name('events.index');
    Route::get('/events/create', CreateEvent::class)->middleware('can:events.create')->name('events.create');
    Route::get('/events/{event}/edit', UpdateEvent::class)->middleware('can:events.update')->name('events.update');
    Route::get('/events/{event}', ViewEvent::class)->middleware('can:events.view')->name('events.view');

    Route::get('/settings/access-control', ManageAccessControl::class)
        ->middleware('can:access-control.manage')
        ->name('settings.access-control');
});
