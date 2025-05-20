<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PDO;

class DBConnService
{
	// singleton instance of this DBConnService class itself
	private static $instance;

	// DB Connection object
	private $conn;

	public static function initSingleton(): bool
	{
		if (!empty(self::$instance)) {
			return false;
		}

		self::$instance = new DBConnService();
		return true;
	}

	public static function getInstance() {
		return self::$instance;
	}

	public function __construct()
	{
		$host = config('database.connections.pgsql.host');
		$port = config('database.connections.pgsql.port');
		$username = config('database.connections.pgsql.username');
		$password = config('database.connections.pgsql.password');
		$database = config('database.connections.pgsql.database');

		try {
			$dsn = "pgsql:host=$host;port=$port;dbname=$database;";
			$this->conn = new PDO(
				$dsn,
				$username,
				$password,
				[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
			);
		} catch (\PDOException $e) {
			die("DB Connection failed due to error: " . $e->getMessage());
		}
	}

	// instance-level func.
	public function getDBConn(): PDO
	{
		return $this->conn;
	}
}

class DBConnServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 */
	public function register(): void
	{
		//
		if (!class_exists(DBConnService::class)) {
			throw new \Exception('DBConnService class not found!');
		}

		$this->app->bind(DBConnService::class, function ($app) {
			return new DBConnService();
		});
	}

	/**
	 * Bootstrap services.
	 */
	public function boot(): void
	{
		//
	}
}
