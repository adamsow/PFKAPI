<?php
	require_once('../APIConfiguration.php');
    require_once('../Services/Litters/LitterService.php');
    
    // Get Litters
    $app->get('/litters', $referer($app), $authorization($app), function() use ($app)
    {
        $log = $app->log;
        $db = $app->db;
        $scope = $app->jwt->scope;
        $access = GetAccess($scope, $app->pages['sites']['litters'], $db, $log);
        $litters = GetAllLitters($db, $log);
        echo '{"access":"' . $access .'", "litters":' .$litters .'}';

    });

    //GET info about litter
    $app->get('/litters/:id', $referer($app), $authorization($app), function($id) use ($app)
    {   
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $litter = GetLitterById($db, $log, $dbw, $id);
        echo $litter;
    } ) ;

    //POST new litter
    $app->post('/litters', $referer($app), $writeAccess($app), function () use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        if (LitterExists($db, $data)) {
            $app->response->status(409);
            echo "litter_exists";
            return;
        }
        
        $result = AddLitter($data, $db, $log, $userId);
        echo $result;
    });

    //PUT udpate litter
    $app->put('/litters/:id', $referer($app), $writeAccess($app), function ($id) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        if (LitterExistsWithAnotherId($db, $data, $id)) {
            $app->response->status(409);
            echo "litter_exists";
            return;
        }
        
            $result = UpdateLitter($data, $db, $log, $userId, $id);
            echo $result;
    });

    //DELETE delete litter
    $app->delete('/litters/:id', $referer($app), $allAccess($app), function ($id) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $scope = $app->jwt->scope;
        $userId = $app->jwt->user_id;
        $result = RemoveLitter($id, $db, $log, $userId);
        echo $result;
    });

 	//GET breeds autocomplete
	$app->get('/breedsautocompletelitters/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$breeds = GetBreedsAutoCompleteFromCommon($db, $filter);
		echo $breeds;
	});

    //GET breedings autocomplete
	$app->get('/breedingsautocompletelitters/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$breeds = GetBreedingsAutoCompleteFromCommon($db, $filter);
		echo $breeds;
	});

    //GET dog autocomplete
	$app->get('/dogsautocompletelitters/:filter/:sex', $referer($app), $writeAccess($app), function ($filter, $sex) use ($app) 
	{
		$db = $app->db;			
		$breeds = GetDogsAutoCompleteFromCommon($db, $filter, $sex);
		echo $breeds;
	});
 
    $app->run();
 