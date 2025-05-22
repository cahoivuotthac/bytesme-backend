<?php

return [

	/*
			 |--------------------------------------------------------------------------
			 | Third Party Services
			 |--------------------------------------------------------------------------
			 |
			 | This file is for storing the credentials for third party services such
			 | as Mailgun, Postmark, AWS and more. This file provides the de facto
			 | location for this type of information, allowing packages to have
			 | a conventional file to locate the various service credentials.
			 |
			 */

	'postmark' => [
		'token' => env('POSTMARK_TOKEN'),
	],

	'ses' => [
		'key' => env('AWS_ACCESS_KEY_ID'),
		'secret' => env('AWS_SECRET_ACCESS_KEY'),
		'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
	],

	'resend' => [
		'key' => env('RESEND_KEY'),
	],

	'geomap' => [
		'key' => env('GEOMAP_API_KEY'),
	],

	'momo' => [
		'base_url' => env('MOMO_BASE_URL'),
		'partner_code' => env('MOMO_PARTNER_CODE'),
		'partner_name' => env('MOMO_PARTNER_NAME'),
		'access_key' => env('MOMO_ACCESS_KEY'),
		'store_id' => env('MOMO_STORE_ID'),
		'secret_key' => env('MOMO_SECRET_KEY'),
	],

	'slack' => [
		'notifications' => [
			'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
			'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
		],
	],

];
