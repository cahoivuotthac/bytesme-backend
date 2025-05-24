<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\ChannelManager;
// use App\Notifications\Channels\ExpoPushChannel;

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
		app(ChannelManager::class)->extend('expo', function ($app) {
			return new \App\Notifications\Channels\ExpoPushChannel();
		});
	}
}
