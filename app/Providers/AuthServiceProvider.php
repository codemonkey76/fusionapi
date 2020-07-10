<?php

namespace App\Providers;

use App\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::viaRequest('service-token', function ($request) {
            try{
                $token = json_decode(Crypt::decryptString($request->get('token')));
                throw_unless(Carbon::parse($token->valid)->greaterThanOrEqualTo(now()), AuthorizationException::class);
                return User::findOrFail($token->user);
            }catch (\Throwable $e){
                throw new AuthenticationException();
            }
        });
    }
}
