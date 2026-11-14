<?php

use App\Exceptions\DomainConflictException;
use App\Models\GameMatch;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $previous = $e->getPrevious();
            $message = match (true) {
                $previous instanceof ModelNotFoundException => sprintf('%s not found.', match ($previous->getModel()) {
                    GameMatch::class => 'Match',
                    default => Str::headline(class_basename($previous->getModel())),
                }),
                $request->route() === null => 'Endpoint not found.',
                default => $e->getMessage() ?: 'Resource not found.',
            };

            return response()->json(['message' => $message], 404);
        });

        $exceptions->render(fn (DomainConflictException $e) => response()->json(['message' => $e->getMessage()], 409));
    })->create();
