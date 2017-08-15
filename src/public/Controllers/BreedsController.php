<?php
	require_once('../APIConfiguration.php');
	require_once('../Services/Breeds/BreedService.php');

	//GET breeds 
	$app->get('/breeds', $referer($app),  $authorization($app), function () use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$scope = $app->jwt->scope;
		$access = GetAccess($scope, $app->pages['sites']['breeds'], $db, $log);
		$breeds = GetAllBreeds($db, $log);
		echo '{"access":"' . $access . '","breeds":' . $breeds . '}';
	});

	//GET info about breed
	$app->get('/breeds/:id', $referer($app), $authorization($app), function ($id) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$dbw = $app->dbw;
		$breed = GetBreedById($db, $log, $id, $dbw);
		echo $breed;
	});

	//POST new breed
	$app->post('/breeds', $referer($app), $writeAccess($app), function () use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
		if (BreedExists($db, $data)) {
			$app->response->status(409);
			echo "breed_exists";
			return;
		}
		
       	$result = AddBreed($data, $db, $log, $userId, $dbw);
       	echo $result;
	});

	//PUT udpate breed
	$app->put('/breeds/:id', $referer($app), $writeAccess($app), function ($id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
		$dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        $colorId = GetExistingBreedId($db, $data);
        if ($colorId > 0 && $colorId !== $id) {
            $app->response->status(409);
            echo "breed_exists";
            return;
		}
		
       	$result = UpdateBreed($data, $db, $log, $userId, $id);
       	echo $result;
	});

	//DELETE delete breed
	$app->delete('/breeds/:id', $referer($app), $allAccess($app), function ($id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $scope = $app->jwt->scope;
        $userId = $app->jwt->user_id;
        $result = RemoveBreed($id, $db, $log, $userId);
        echo $result;
	});
	
	$app->run();