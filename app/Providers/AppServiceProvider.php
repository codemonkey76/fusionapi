<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Password::defaults(fn() => Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised());
    }
}
