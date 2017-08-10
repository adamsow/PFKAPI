<?php
    require_once('../APIConfiguration.php');
    require_once('../Services/Account/AccountService.php');

    //GET token
	$app->get('/token/:name/:password', $referer($app), function ($name, $password) use ($app) 
	{
		$log = $app->log;
        $dbw = $app->dbw;
        $secret = $app->secret;
        $token = GetToken($name, $password, $dbw, $log, $secret);
        if($token === false)
        {
            $log -> addInfo("User " . $name . " not found");
            $app->response->status(401);
            echo json_encode($app->err['errors']['UserNotFound']);
            return;
        }
                
        echo json_encode($token);
	});
	
	//GET roles for manage access page
	$app->get('/roles', $referer($app), function () use ($app) 
	{
		$log = $app->log;
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
	});
	
	//POST access rights for API pages
	$app->post('/roles', function () use ($app) 
	{
		$log = $app->log;
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
	});
    

$app->run();