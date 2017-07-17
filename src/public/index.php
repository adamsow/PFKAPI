<?php
	header('Content-Type: text/javascript; charset=utf8');
	date_default_timezone_set('Europe/Warsaw');
	require '../vendor/autoload.php';
	$settings = require '../private/local.php';
	include('functions.php');

	$app = new \Slim\Slim($settings);
	//set up logging
	$app->container->singleton('log', function () {
		$logger = new \Monolog\Logger('PFK_API');
		$file_handler = new \Monolog\Handler\RotatingFileHandler("../logs/app.log", 3);
		$logger->pushHandler($file_handler);
		return $logger;
	});
	//set up JWT tokens
	$app->add(new \Slim\Middleware\JwtAuthentication([
		"path" => "/",
		"logger" => $app->log,
		"passthrough" => ["/token", "/breedings", "/studs", "/litters", "/exhibition"],	
		"secret" => $settings['settings']['secret'],
		"callback" => function ($options) use ($app) {
			$app->jwt = $options["decoded"];
        }
	]));

	//set up PFK DB connection
	$app->container->singleton('db', function ($c) 
	{
		try
		{
			$db = $c['settings']['db'];
			$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'] . ";charset=utf8",
			$db['user'], $db['pass']);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			//$pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
			return $pdo;
		}
		catch(Exception $e){
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	});
	
	//set up Wordpress DB connection
	$app->container->singleton('dbw', function ($c) 
	{
		try
		{
			$db = $c['settings']['dbw'];
			$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'] . ";charset=utf8",
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
	
	//set up secret
	$app->container->singleton('secret', function ($c) 
	{
		return $c['settings']['settings']['secret'];
	});
	
	//GET breedings
	$app->get('/breedings', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(strrpos($referer, "https://pfk.org.pl") === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;
			/*if (in_array("administrator", $app->jwt->scope)) 
			{
				echo "super";
			} 
			else 
			{
				No scope so respond with 401 Unauthorized 
				$app->response->status(401);
			}
			*/
			
			$breedings = GetBreedings($db, $log);
			echo $breedings;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//GET studs
	$app->get('/studs', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(strrpos($referer, "https://pfk.org.pl") === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;			
			$studs = GetStuds($db, $log);
			echo $studs;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//GET litters
	$app->get('/litters', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(strrpos($referer, "https://pfk.org.pl") === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;			
			$litters = GetLitters($db, $log);
			echo $litters;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//GET exhibition data
	$app->get('/exhibition', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(strrpos($referer, "https://pfk.org.pl") === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;			
			$exhibitionData = GetExhibitionData($db, $log);
			echo $exhibitionData;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//POST exhibition data
	$app->post('/exhibition', function () use ($app) 
	{	
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(strrpos($referer, "https://pfk.org.pl") === false)
			{
				//$app->response->status(401);
				//return;
			}
			$db = $app->db;			
			$payload = stripslashes($_POST["payload"]);
			$data = json_decode($payload);
			$result = SaveExhibitionData($db, $log, $data);
			
			echo $result;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
			echo $e->getMessage();
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
