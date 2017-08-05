<?php
require_once('../APIConfiguration.php');

	//GET info about particiapnt 
	$app->get('/members/:department/:filter',  function ($department, $filter) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$dbw = $app->dbw;
		$scope = $app->jwt->scope;
		throw new Exception("asdasd");
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
	});
	
	$app->run();