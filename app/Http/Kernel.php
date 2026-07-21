<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // Doit tourner en premier : lit X-Forwarded-Proto envoye par le
        // proxy Railway pour que Laravel sache que la requete est en https
        // (sinon asset()/url() generent des liens http:// bloques en tant
        // que "Mixed Content" par le navigateur).
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'all' => \App\Http\Middleware\AllProfiles::class,
        'admin' => \App\Http\Middleware\Admin::class,
        'agent' => \App\Http\Middleware\Agent::class,
        'cashier' => \App\Http\Middleware\Cashier::class,
        'csa' => \App\Http\Middleware\Csa::class,
        'retail_agent' => \App\Http\Middleware\RetailAgent::class,
        'technical_support' => \App\Http\Middleware\TechnicalSupport::class,
        'owner' => \App\Http\Middleware\OwnerTrans::class,
        'cors' => \App\Http\Middleware\Cors::class,
    ];
}
