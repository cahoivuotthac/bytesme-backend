<?php
return [
	'paths' => ['*'], // Allow CORS for all paths during development
	'allowed_origins' => ['http://localhost:8081', 'exp://192.168.2.9/8081'], // Front-end origin
	'allowed_methods' => ['*'],
	'allowed_headers' => ['*'],
	'exposed_headers' => [],
	'max_age' => 0,
	'supports_credentials' => true,
];