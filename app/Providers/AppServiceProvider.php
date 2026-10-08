<?php

namespace App\Providers;

use App\Models\ChurchSetting;
use App\Models\SmallGroup;
use App\Models\User;
use App\Policies\SmallGroupPolicy;
use App\Policies\UserPolicy;
use App\Services\AccessManager;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(AccessManager::class);
        $this->app->singleton(ChurchSetting::class, fn () => Schema::hasTable('church_settings')
            ? ChurchSetting::current()
            : ChurchSetting::fallback());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(SmallGroup::class, SmallGroupPolicy::class);
        if (Schema::hasTable('church_settings')) {
            $churchSettings = app(ChurchSetting::class);
            config(['app.name' => $churchSettings->name, 'app.timezone' => $churchSettings->timezone]);
            date_default_timezone_set($churchSettings->timezone);
            App::setLocale($churchSettings->locale);
        }
        View::composer('*', function ($view): void {
            $view->with('churchSettings', app(ChurchSetting::class));
        });

        foreach (PermissionRegistry::keys() as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }

        Gate::define('access-control.manage', fn (User $user): bool => $user->isActive() && $user->isAdmin());
    }
}
