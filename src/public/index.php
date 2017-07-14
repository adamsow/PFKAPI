<?php
	header('Access-Control-Allow-Origin: https://pfk.org.pl');
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
	header('Access-Control-Allow-Headers: X-Custom-Header');

	date_default_timezone_set('Europe/Warsaw');
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
		"secret" => $settings['settings']['secret'],
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
	
	$app->container->singleton('dbw', function ($c) 
	{
		try
		{
			$db = $c['settings']['dbw'];
			$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],
			$db['user'], $db['pass']);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			return $pdo;
		}
		catch(Exception $e)
		{
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	});
	
	$app->container->singleton('secret', function ($c) 
	{
		return $c['settings']['settings']['secret'];
	});
	
	$app->get('/hello/:name', function ($name) use ($app) 
	{
		try
		{
			$log = $app->log;
			$db = $app->db;
			$dbw = $app->dbw;
			if (in_array("administrator", $app->jwt->scope)) 
			{
				echo "super";
			} 
			else 
			{
				/* No scope so respond with 401 Unauthorized */
			//	$app->response->status(401);
			}
			
			hello($db, $log, $dbw);	
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	$app->get('/token/:name/:password', function ($name, $password) use ($app) 
	{
		try
		{
			$err = require_once('errors.php');
			$log = $app->log;
			$dbw = $app->dbw;
			$secret = $app->secret;

			$token = GetToken($name, $password, $dbw, $log, $secret);
			if($token === false)
			{
				$log -> addInfo("User " . $name . " not found");
				$app->response->status(401);
				echo json_encode($err['errors']['UserNotFound']);
				return;
			}
					
			echo json_encode($token);
		}	
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	$app->run();
