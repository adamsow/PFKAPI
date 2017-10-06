<?php
	header('Content-Type: text/javascript; charset=utf8');
	date_default_timezone_set('Europe/Warsaw');
	require '../../vendor/autoload.php';
	$err = require_once 'errors.php';
	$pages = require_once 'pages.php';
	$settings = require_once '../../private/local.php';
	require_once 'CommonFunctions.php';
	require_once 'Services/Account/AccountService.php';

	$app = new \Slim\Slim($settings);
	$app->pages = $pages;
	$app->err = $err;
	//set up logging
	$app->container->singleton('log', function () {
		$logger = new \Monolog\Logger('PFK_API');
		$file_handler = new \Monolog\Handler\RotatingFileHandler("../../logs/app.log", 5);
		$logger->pushHandler($file_handler);
		return $logger;
	});
		   
	//set up JWT tokens
	$app->add(new \Slim\Middleware\JwtAuthentication([
		"path" => "/",
		"logger" => $app->log,
		"passthrough" => ["/token", "/breedings", "/studs", "/publiclitters", "/exhibition","/dogsautocomplete", "/memberCertificate", "/breederCertificate"],	
		"secret" => $settings['settings']['secret'],
		'displayErrorDetails' => false,
		"callback" => function ($options) use ($app) {
			$app->jwt = $options["decoded"];
        }
	]));
	
	
	$app->config('debug', false);

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

    $app->error(function ( Exception $e ) use ($app) 
    {
            $app->log->addError($e->getMessage());
            $app->log->addError($e->getTraceAsString());
			$app->response->status(500);
			echo "Error occured";
    });

    //setup referef check
    $referer = function($app)
    {
        return function() use ($app)
        {
            $ref = $app->request->getReferrer();
            if (strrpos($ref, "https://pfk.org.pl") === false) 
            {
                $app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				$app->stop();
            }
        };
	};

	$authorization = function($app)
    {
        return function($accessPage) use ($app)
        {
			$scope = $app->jwt->scope;
			$db = $app->db;
			$page = GetPage($accessPage, $app->pages);
    		$access = GetAccess($scope, $page, $db);
			if($access === NULL || $access === "NO ACCESS" || $access === '')
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				$app->stop();
			}
        };
	};
	
	$getTokenFromParams = function($app)
	{
		return function($page) use ($app)
		{
			 $token = $page->params["token"];
			 $app->log -> addInfo("Getting members for department: ". $token);
			 if (false === empty($token)) {
				 $app->request->headers->set("Authorization", "Bearer $token");
				 $decoded = DecodeToken($token, '8SaXjUDp1uQM8cPNACe0');
				 $app->jwt = $decoded;
			 }
		};
	};

	$writeAccess = function($app)
	{
		return function($accessPage) use ($app)
		{
			$scope = $app->jwt->scope;
			$db = $app->db;
			$page = GetPage($accessPage, $app->pages);
    		$access = GetAccess($scope, $page, $db);
			if (!HasWriteAccess($access)) 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				$app->stop();
			}
		};
	};

	$allAccess = function($app)
	{
		return function($accessPage) use ($app)
		{
			$scope = $app->jwt->scope;
			$db = $app->db;
			$page = GetPage($accessPage, $app->pages);
    		$access = GetAccess($scope, $page, $db);
			if (!HasAllAccess($access)) 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				$app->stop();
			}
		};
	};