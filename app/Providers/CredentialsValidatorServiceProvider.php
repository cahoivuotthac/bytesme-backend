<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CredentialsValidatorService;

class CredentialsValidatorServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 */
	public function register(): void
	{
		$this->app->singleton(CredentialsValidatorService::class);
	}

	/**
	 * Bootstrap services.
	 */
	public function boot(): void
	{
		//
	}
}
