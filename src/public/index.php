<?php
	date_default_timezone_set('America/New_York');
	require '../vendor/autoload.php';
	$settings = require '../private/local.php';
	include('functions.php');

	$app = new \Slim\Slim($settings);
	$app->container->singleton('log', function () {
		$logger = new \Monolog\Logger('PFK_API');
		$file_handler = new \Monolog\Handler\RotatingFileHandler("../logs/app.log", 5);
		$logger->pushHandler($file_handler);
		return $logger;
	});
	$app->add(new \Slim\Middleware\JwtAuthentication([
		"path" => "/",
		"logger" => $app->log,
		"passthrough" => "/token",	
		"secret" => "secret",
		"callback" => function ($options) use ($app) {
			$app->jwt = $options["decoded"];
        }
	]));

	$app->container->singleton('db', function ($c) 
	{
		try
		{
			$db = $c['settings']['db'];
			$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],
			$db['user'], $db['pass']);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			return $pdo;
		}
		catch(Exception $e){
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	});
	
	$app->get('/hello/:name', function ($name) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		if (in_array("delete", $app->jwt->scope)) {
			echo "super";
		} else {
			/* No scope so respond with 401 Unauthorized */
			$app->response->status(401);
		}
		
		hello($db, $log);			
	});
	
	$app->get('/token', function () use ($app) 
	{
		GetToken();			
	});
	
	$app->run();
