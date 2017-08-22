<?php
	require_once('../APIConfiguration.php');
	require_once('../Services/Colors/ColorService.php');

	//GET colors 
	$app->get('/colors', $referer($app),  $authorization($app), function () use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$scope = $app->jwt->scope;
		$access = GetAccess($scope, $app->pages['sites']['colors'], $db, $log);
		$colors = GetAllColors($db, $log);
		echo '{"access":"' . $access . '","colors":' . $colors . '}';
	});

	//GET info about color
	$app->get('/colors/:id', $referer($app), $authorization($app), function ($id) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$dbw = $app->dbw;
		$color = GetColorById($db, $log, $id, $dbw);
		echo $color;
	});

	//POST new color
	$app->post('/colors', $referer($app), $writeAccess($app), function () use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
		if (ColorExists($db, $data)) {
			$app->response->status(409);
			echo "color_exists";
			return;
		}
		
       	$result = AddColor($data, $db, $log, $userId);
       	echo $result;
	});

	//PUT udpate color
	$app->put('/colors/:id', $referer($app), $writeAccess($app), function ($id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
		$dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        $colorId = GetExistingColorId($db, $data);
        if ($colorId > 0 && $colorId !== $id) {
            $app->response->status(409);
            echo "color_exists";
            return;
		}
		
       	$result = UpdateColor($data, $db, $log, $userId, $id);
       	echo $result;
	});

	//DELETE delete color
	$app->delete('/colors/:id', $referer($app), $allAccess($app), function ($id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $scope = $app->jwt->scope;
        $userId = $app->jwt->user_id;
        $result = RemoveColor($id, $db, $log, $userId);
        echo $result;
	});
	
	$app->run();