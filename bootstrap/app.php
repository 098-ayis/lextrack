<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ClientMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware):void {
	$middleware->trustProxies(
		at: '*',
	);

        $middleware->alias([
            'client' => ClientMiddleware::class,
            'admin' => AdminMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->is('chatbot/message'),
        );

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('chatbot/message')) {
                return null;
            }

            // Keep technical exception details and HTTP failure codes out of
            // the client chat. The exception class is enough for server logs.
            Log::error('Unhandled client chatbot exception.', [
                'exception_class' => $exception::class,
            ]);

            return response()->json([
                'reply' => 'I’m sorry, but I could not complete that request right now. Please try again or use the Client Portal page for this information.',
            ]);
        });
    })->create();
