<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(fn (Request $request) => match (true) {
            $request->user()?->isPetugas() => route('finance.payments.index'),
            $request->user()?->isBendaharaUnit() => route('finance.bills.index'),
            $request->user()?->isGuardian() => route('finance.bills.index'),
            default => route('dashboard'),
        });

        $middleware->alias([
            'role.access' => \App\Http\Middleware\EnsureRolePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
