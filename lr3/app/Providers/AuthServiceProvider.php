<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Gate for updating items - only owner or admin can update
        Gate::define('update-item', function ($user, $item) {
            return $item->user_id === $user->id || $user->is_admin;
        });

        // Gate for deleting items - only owner can soft delete their own items
        Gate::define('delete-item', function ($user, $item) {
            return $item->user_id === $user->id || $user->is_admin;
        });

        // Gate for permanently deleting items - only admin can do this
        Gate::define('force-delete-item', function ($user, $item) {
            return $user->is_admin;
        });

        // Gate for restoring items - only admin can do this
        Gate::define('restore-item', function ($user, $item) {
            return $user->is_admin;
        });

        // Gate for viewing any user's items - only admin can view all users' items
        Gate::define('view-user-items', function ($user, $itemUser) {
            return $user->is_admin || $user->id === $itemUser->id;
        });
    }
}
