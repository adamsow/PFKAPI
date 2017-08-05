<?php
	header('Content-Type: text/javascript; charset=utf8');
	date_default_timezone_set('Europe/Warsaw');
	require '../vendor/autoload.php';
	$err = require_once 'errors.php';
	$pages = require_once 'pages.php';
	$settings = require_once '../private/local.php';
	include_once('functions.php');

	$app = new \Slim\Slim($settings);
	$app->pages = $pages;
	$app->err = $err;
	//set up logging
	$app->container->singleton('log', function () {
		$logger = new \Monolog\Logger('PFK_API');
		$file_handler = new \Monolog\Handler\RotatingFileHandler("../logs/app.log", 5);
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
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;
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
			if(CheckReferer($referer) === false)
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
			if(CheckReferer($referer) === false)
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
	
	//GET exhibition data for form
	$app->get('/exhibition', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
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
	
	//POST exhibition data form
	$app->post('/exhibition', function () use ($app) 
	{	
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				//$app->response->status(401);
				//return;
			}
			$db = $app->db;			
			$payload = stripslashes($_POST["payload"]);
			$log -> addInfo($payload);
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
	
	//GET token
	$app->get('/token/:name/:password', function ($name, $password) use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				return;
			}
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
	
	//GET exhibitions data 
	$app->get('/exhibitions/:filter', function ($filter) use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if($access !== NULL && $access !== "NO ACCESS")
			{
				$exhibitions = GetExhibitions($db, $log, $filter);
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}

			echo '{"access":"' . $access . '","exhibitions":' . $exhibitions . '}';
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//Insert new exhibition 
	$app->post('/exhibitions', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if(HasWriteAccess($access))
			{
				$body = $app->request->getBody();
				$userId = $app->jwt->user_id;
				$data = json_decode($body);
				$result = AddExhibition($data, $db, $log, $userId);
				echo $result;
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//Update existing exhibition 
	$app->put('/exhibitions', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if(HasWriteAccess($access))
			{
				$body = $app->request->getBody();
				$userId = $app->jwt->user_id;
				$data = json_decode($body);
				$result = UpdateExhibition($data, $db, $log, $userId);
				echo $result;
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//Delete exhibition 
	$app->delete('/exhibitions/:id', function ($id) use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if(HasAllAccess($access))
			{
				$userId = $app->jwt->user_id;
				$data = json_decode($body);
				$result = RemoveExhibition($id, $db, $log, $userId);
				echo $result;
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//GET exhibition by ID 
	$app->get('/exhibitionById/:id', function ($id) use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;
			$dbw = $app->dbw;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if($access !== NULL && $access !== "NO ACCESS")
			{
				$exhibition = GetExhibitionById($db, $log, $id, $dbw);
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}		

			echo $exhibition;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//GET departments 
	$app->get('/departments', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if($access !== NULL && $access !== "NO ACCESS")
			{
				$departments = GetDepartments($db, $log);
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}		

			echo $departments;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//GET roles for manage access page
	$app->get('/roles', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
			$dbw = $app->dbw;
			$db = $app->db;
			$scope = $app->jwt->scope;
			if (in_array("administrator", $scope)) 
			{
				$roles = GetRoles($dbw, $log);
				$sites = GetApiSites($db, $log);
				$assignments = GetRoleAssignments($db, $log);
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}		

			echo '{"roles":' . $roles . ',"sites":' . $sites . ',"assignments":' . $assignments . '}';
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//POST access rights for API pages
	$app->post('/roles', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
			$db = $app->db;
			$scope = $app->jwt->scope;
			if (in_array("administrator", $scope)) 
			{
				$body = $app->request->getBody();
				$data = json_decode($body);
				$result = SaveRoles($data, $db, $log);
				echo $result;
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}		
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//GET exhibition participants 
	$app->get('/participants/:id', function ($id) use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;

			}
			$db = $app->db;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if($access !== NULL && $access !== "NO ACCESS")
			{
				$participants = GetExhibitionParticipants($db, $log, $id);
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}

			echo '{"access":"' . $access . '","participants":' . $participants . '}';
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//GET all info for exhibition (for csv files) 
	$app->get('/participantsAll/:id/:filter', function ($id, $filter) use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
			$db = $app->db;
			$dbw = $app->dbw;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if($access !== NULL && $access !== "NO ACCESS")
			{
				$participants = GetExhibitionParticipantsAll($db, $log, $id, $filter, $dbw);
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}

			echo $participants;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//GET all info for exhibition (for csv files) 
	$app->get('/applicationConsts', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
			$db = $app->db;
			$dbw = $app->dbw;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if($access !== NULL && $access !== "NO ACCESS")
			{
				$consts = GetApplicationConsts($db, $log);
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}

			echo $consts;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//add new participant 
	$app->post('/participants', function () use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
			$db = $app->db;
			$dbw = $app->dbw;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if(HasWriteAccess($access))
			{
				$body = $app->request->getBody();
				$userId = $app->jwt->user_id;
				$data = json_decode($body);
				$result = AddParticipant($data, $db, $log, $userId);
				echo $result;
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}

			echo $consts;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//GET info about particiapnt 
	$app->get('/participantById/:id', function ($id) use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
			$db = $app->db;
			$dbw = $app->dbw;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if($access !== NULL && $access !== "NO ACCESS")
			{
				$participants = GetExhibitionParticipant($db, $log, $id, $dbw);
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}

			echo $participants;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//update existing participant 
	$app->put('/participants/:id', function ($id) use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
			$db = $app->db;
			$dbw = $app->dbw;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if(HasWriteAccess($access))
			{
				$body = $app->request->getBody();
				$userId = $app->jwt->user_id;
				$data = json_decode($body);
				$result = UpdateParticipant($id, $data, $db, $log, $userId);
				echo $result;
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}

			echo $consts;
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	//Delete participant 
	$app->delete('/participants/:id', function ($id) use ($app) 
	{
		$log = $app->log;
		try
		{
			$referer = $app->request->getReferrer();
			if(CheckReferer($referer) === false)
			{
				$app->response->status(401);
				return;
			}
			$db = $app->db;
			$scope = $app->jwt->scope;
			$access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
			if(HasAllAccess($access))
			{
				$userId = $app->jwt->user_id;
				$data = json_decode($body);
				$result = RemoveParticipant($id, $db, $log, $userId);
				echo $result;
			}
			else 
			{
				$app->response->status(401);
				echo json_encode($app->err['errors']['UnauthorizedAccess']);
				return;
			}
		}
		catch(Exception $e)
		{
			$log -> addError($e->getMessage());
			$app->response->status(500);
		}
	});
	
	$app->run();
