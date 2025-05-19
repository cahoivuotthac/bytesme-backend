<?php

use App\Http\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
	->withRouting(
		web: __DIR__ . '/../routes/web.php',
		commands: __DIR__ . '/../routes/console.php',
		channels: __DIR__ . '/../routes/channels.php',
		health: '/up',
	)
	->withMiddleware(function (Middleware $middleware) {
		// Add Sanctum middleware
		$middleware->alias([
			'auth.sanctum' => EnsureFrontendRequestsAreStateful::class,
		]);

		// Configure API middleware group
		$middleware->group('api', middleware: [
			EnsureFrontendRequestsAreStateful::class,
			\Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
			\Illuminate\Routing\Middleware\SubstituteBindings::class,
		]);
	})
	->withExceptions(function (Exceptions $exceptions): void {
		//
	})->create();
