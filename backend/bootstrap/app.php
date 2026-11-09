<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        // Behind the proxies of the server (TRUSTED_PROXIES=*), Laravel must read the original scheme
        // and host from X-Forwarded-*. Empty by default, so local development trusts no proxy.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(fn (AuthenticationException $exception, Request $request) => $request->expectsJson()
            ? response()->json(['message' => 'Sessão expirada. Entre novamente.'], 401)
            : null);

        $exceptions->render(fn (HttpException $exception, Request $request) => $request->expectsJson() && $exception->getStatusCode() === 403
            ? response()->json(['message' => 'Você não tem permissão para esta ação.'], 403)
            : null);

        $exceptions->render(fn (HttpException $exception, Request $request) => $request->expectsJson() && $exception->getStatusCode() === 419
            ? response()->json(['message' => 'Sua sessão expirou. Recarregue a página e tente novamente.'], 419)
            : null);

        $exceptions->render(fn (NotFoundHttpException $exception, Request $request) => $request->expectsJson()
            ? response()->json(['message' => 'Registro não encontrado.'], 404)
            : null);
    })->create();
