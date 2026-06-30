<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Leave JSON requests and standard HTTP exceptions (404, 419, 503…) to default handling
            if ($request->expectsJson()) return null;
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) return null;
            if ($e instanceof \Illuminate\Auth\AuthenticationException) return null;

            // All other unhandled exceptions → custom 500 view
            return response()->view('errors.500', ['exception' => $e], 500);
        });
    })->create();
