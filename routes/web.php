<?php

use App\Livewire\SmallGroup\CreateSmallGroup;
use App\Livewire\SmallGroup\IndexSmallGroup;
use App\Livewire\SmallGroup\ManageLessons;
use App\Livewire\SmallGroup\ManageMembers;
use App\Livewire\SmallGroup\UpdateSmallGroup;
use App\Livewire\SmallGroup\ViewLesson;
use App\Livewire\SmallGroup\ViewSmallGroup;
use App\Livewire\User\CreateUser;
use App\Livewire\User\IndexUser;
use App\Livewire\User\UpdateUser;
use App\Livewire\User\UserLocationsMap;
use App\Livewire\User\ViewUser;
use App\Livewire\Attendance\CreateEvent;
use App\Livewire\Attendance\IndexEvent;
use App\Livewire\Attendance\UpdateEvent;
use App\Livewire\Attendance\ViewEvent;
use App\Http\Controllers\MemberListExportController;
use App\Http\Controllers\Auth\LoginController;
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

        $path = 'profile-photos/' . $filename;
        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    })->where('filename', '[^/]+')->name('profile-photo');

    Route::get('/small-group-photos/{filename}', function (string $filename) {
        abort_if($filename === '' || basename($filename) !== $filename, 404);

        $path = 'small-group-photos/' . $filename;
        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    })->where('filename', '[^/]+')->name('small-group-photo');

    Route::get('/', function () {
        return view('home');
    })->name('home');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

// User/Member Management
Route::get('/users', IndexUser::class)->name('users.index');
Route::get('/users/export', MemberListExportController::class)->name('users.export');
Route::get('/users/map', UserLocationsMap::class)->name('users.map');
Route::get('/users/create', CreateUser::class)->name('users.create');
Route::get('/users/{user}', ViewUser::class)->name('users.show');
Route::get('/users/{user}/edit', UpdateUser::class)->name('users.edit');

// Small Group Management
Route::get('/small-groups', IndexSmallGroup::class)->name('small-groups.index');
Route::get('/small-groups/create', CreateSmallGroup::class)->name('small-groups.create');
Route::get('/small-groups/{smallGroup}', ViewSmallGroup::class)->name('small-groups.show');
Route::get('/small-groups/{smallGroup}/edit', UpdateSmallGroup::class)->name('small-groups.edit');
Route::get('/small-groups/{smallGroup}/members', ManageMembers::class)->name('small-groups.members');
Route::get('/small-groups/{smallGroup}/lessons', ManageLessons::class)->name('small-groups.lessons');
Route::get('/small-groups/{smallGroup}/lessons/{lesson}', ViewLesson::class)->name('small-groups.lessons.show');

// Event Management
Route::get('/events', IndexEvent::class)->name('events.index');
Route::get('/events/create', CreateEvent::class)->name('events.create');
Route::get('/events/{event}/edit', UpdateEvent::class)->name('events.update');
Route::get('/events/{event}', ViewEvent::class)->name('events.view');
});
