<?php
	date_default_timezone_set('America/New_York');
	require '../vendor/autoload.php';
	//$config = include(__DIR__ . '/../private/local.php');
	//$host = $config['db']['host'];
	//$dbname = $config['db']['name'];
	//$user = $config['db']['user'];
//	$password = $config['db']['password'];
	//$db = new PDO("mysql:host=$host;dbname=$dbname", $user, $config['db']['password'] );
	//$db -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$app = new \Slim\Slim(["settings" => $config]);
	$app->container->singleton('log', function () {
		$logger = new \Monolog\Logger('my_logger');
		$file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
		$logger->pushHandler($file_handler);
		return $logger;
	});
	$app->container->singleton('db', function ($c) {
		$db = $c['settings']['db'];
		$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],
        $db['user'], $db['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
	});
	
	$app->get('/hello/:name', function ($name) use ($app) {
		//$this->logg->addInfo("Something interesting happened");
		$log = $app->log;
		$log -> addInfo("Something interesting happened");
		//$log -> addInfo($app->db);

		echo "Hello, " . $name;
	});
	$app->run();
