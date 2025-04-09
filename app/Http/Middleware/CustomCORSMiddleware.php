<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomCORSMiddleware
{
	public function handle(Request $request, Closure $next)
	{
		Log::info("Custom CORS middleware executed", [
			'method' => $request->method(),
			'url' => $request->fullUrl(),
			'origin' => $request->header('Origin'),
		]);

		$response = $next($request);

		// Add CORS headers
		$response->headers->set('Access-Control-Allow-Origin', $request->header('Origin') ?? '*');
		$response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
		$response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN');
		$response->headers->set('Access-Control-Allow-Credentials', 'true');

		// Handle preflight OPTIONS request
		if ($request->isMethod('OPTIONS')) {
			return response('', 200)
				->header('Access-Control-Allow-Origin', $request->header('Origin') ?? '*')
				->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
				->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN')
				->header('Access-Control-Allow-Credentials', 'true')
				->header('Access-Control-Max-Age', '86400'); // 24 hours
		}

		return $response;
	}
}