<?php

namespace App\Providers;

use App\Models\Mecanicien;
use App\Observers\MecanicienObserver;
use App\Models\Reception;
use App\Observers\ReceptionObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Mecanicien::observe(MecanicienObserver::class);
        Reception::observe(ReceptionObserver::class);
        // ---------
        // Définir l'autorisation globale pour la caisse à outils
        Gate::define('gerer-outils', function (User $user) {
            return in_array($user->role, ['admin', 'caisse_outils']);
        });
        // --------
    }
}
