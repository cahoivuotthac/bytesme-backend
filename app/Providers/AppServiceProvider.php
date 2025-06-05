<?php

namespace App\Providers;

use App\Channels\ExpoPushChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{

	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		Notification::extend('expo_push', function ($app) {
			return $app->make(ExpoPushChannel::class);
		});
	}
}
