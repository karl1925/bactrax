<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureHasPermission;
use App\Http\Middleware\EnsureHasPrivilege;
use App\Http\Middleware\EnsureOfficeScope;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Maicol07\OpenIDConnect\OIDCClientException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'permission' => EnsureHasPermission::class,
            'privilege' => EnsureHasPrivilege::class,
            'office.scope' => EnsureOfficeScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // $exceptions->render(function (OIDCClientException $e, Request $request) {
        //     if ($request->routeIs('oidc.callback') && $request->query('error') === 'access_denied') {
        //         return redirect()->route('access.request')->with('oidc_error_description', $request->query('error_description', $e->getMessage()));
        //     }
        //     return null;
        // });
    })->create();
