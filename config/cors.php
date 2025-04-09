<?php
return [
	'paths' => ['*'], // Allow CORS for all paths during development
	'allowed_origins' => ['http://localhost:8081'], // Allow all origins during development
	'allowed_methods' => ['*'],
	'allowed_headers' => ['*'],
	'exposed_headers' => [],
	'max_age' => 0,
	'supports_credentials' => true,
];