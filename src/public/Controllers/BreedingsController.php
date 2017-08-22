<?php
	require_once('../APIConfiguration.php');
	require_once('../Services/Breedings/BreedingService.php');

	//GET breedings 
	$app->get('/allBreedings/:filter', $referer($app),  $authorization($app), function ($filter) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$scope = $app->jwt->scope;
		$access = GetAccess($scope, $app->pages['sites']['breedings'], $db, $log);
		$breedings = GetAllBreedings($db, $log, $filter);
		echo '{"access":"' . $access . '","breedings":' . $breedings . '}';
	});

	//GET info about breeding
	$app->get('/getBreedingById/:id', $referer($app), $authorization($app), function ($id) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$dbw = $app->dbw;
		$breeding = getBreedingById($db, $log, $id, $dbw);
		echo $breeding;
	});

	//POST new breeding
	$app->post('/newBreeding', $referer($app), $writeAccess($app), function () use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
		if (BreedingExists($db, $data)) {
			$app->response->status(409);
			echo "breed_exists";
			return;
		}
		
       	$result = AddBreeding($data, $db, $log, $userId, $dbw);
       	echo $result;
	});

	//PUT udpate breeding
	$app->put('/updateBreeding/:id', $referer($app), $writeAccess($app), function ($id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
		$dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        if (BreedingExistsWithAnotherId($db, $data, $id)) {
            $app->response->status(409);
            echo "breeding_exists";
            return;
		}
		
       	$result = UpdateBreeding($data, $db, $log, $userId, $id);
       	echo $result;
	});

	//DELETE delete breeding
	$app->delete('/deleteBreeding/:id', $referer($app), $allAccess($app), function ($id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $scope = $app->jwt->scope;
        $userId = $app->jwt->user_id;
        $result = RemoveBreeding($id, $db, $log, $userId);
        echo $result;
	});

	//GET breed autocomplete
	$app->get('/breedsautocompletebreedings/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$breeds = GetBreedsAutoCompleteFromCommon($db, $filter);
		echo $breeds;
	});
	
	$app->run();