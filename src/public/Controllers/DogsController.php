<?php
	require_once('../APIConfiguration.php');
    require_once('../Services/Dogs/DogsService.php');
    
    // Get dogs with lineage data
    $app->get('/dogswithlineage', $referer($app), $authorization($app), function() use ($app)
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $scope = $app->jwt->scope;
        $access = GetAccess($scope, $app->pages['sites']['lineages'], $db, $log);
        $data = GetDogsWithLineages($db, $log);
        echo '{"access":"' . $access .'", "data":' .$data .'}';
    });

    //GET info about dog
    $app->get('/dogswithlineage/:id', $referer($app), $authorization($app), function($id) use ($app)
    {   
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $data = GetDogById($db, $log, $dbw, $id);
        echo $data;
    });

    //POST new dog
    $app->post('/dogswithlineage', $referer($app), $writeAccess($app), function () use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        if (DogExists($db, $data)) {
            $app->response->status(409);
            echo "dog_exists";
            return;
        }
        if ($data->nrPedigree != '') {
            if (PedExists($db, $data)) {
                $app->response->status(409);
                echo "ped_exists";
                return;
            }
        }
        
        $result = AddDog($data, $db, $log, $userId, false);
        echo $result;
    });

    //PUT udpate dog
    $app->put('/dogswithlineage/:id', $referer($app), $writeAccess($app), function ($id) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        if (DogExistsWithAnotherId($db, $data, $id)) {
            $app->response->status(409);
            echo "dog_exists";
            return;
        }
        if ($data->nrPedigree != '') {
            if (PedExistsWithAnotherId($db, $data, $id)) {
                $app->response->status(409);
                echo "ped_exists";
                return;
            }
        }
        
        $result = UpdateDog($data, $db, $log, $userId, $id);
        echo $result;
    });

    //DELETE delete dog
    $app->delete('/dogswithlineage/:id', $referer($app), $allAccess($app), function ($id) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $userId = $app->jwt->user_id;
        $result = RemoveDog($id, $db, $log, $userId);
        echo $result;
    });

    //GET breeds autocomplete
	$app->get('/breedsautocompletedogswithlineage/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$breeds = GetBreedsAutoCompleteFromCommon($db, $filter);
		echo $breeds;
    });
    
    //GET colors autocomplete
	$app->get('/colorsautocompletedogswithlineage/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$colors = GetColorsAutoCompleteFromCommon($db, $filter);
		echo $colors;
    });

    //GET persons autocomplete
	$app->get('/personsautocompletedogswithlineage/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$persons = GetPersonsAutoCompleteFromCommon($db, $filter);
		echo $persons;
    });

     //GET dog autocomplete
	$app->get('/dogsautocompletedogswithlineage/:filter/:sex', $referer($app), $writeAccess($app), function ($filter, $sex) use ($app) 
	{
		$db = $app->db;			
		$dogs = GetDogsAutoCompleteFromCommon($db, $filter, $sex);
		echo $dogs;
    });

    //GET lineage  
	$app->get('/dogswithlineageshowlineage/:id/:generations', $referer($app), $authorization($app), function ($id, $generations) use ($app) 
	{
        $db = $app->db;			
        $log = $app->log;
		$lineage = GetLineage($db, $id, $log, $generations);
		echo $lineage;
    });

    //POST lineage parent  
	$app->post('/dogswithlineagesetparent', $referer($app), $writeAccess($app), function () use ($app) 
	{
        $db = $app->db;			
        $log = $app->log;
        $body = $app->request->getBody();
        $data = json_decode($body);
        $userId = $app->jwt->user_id;
		$result = SetParent($db, $log, $data, $userId);
		echo $result;
    });

    //DELETE lineage parent  
    $app->delete('/dogswithlineagedeleteparent/:childId/:isFather', $referer($app), $writeAccess($app), 
        function ($childId, $isFather) use ($app) 
	{
        $db = $app->db;			
        $log = $app->log;
        $userId = $app->jwt->user_id;
		$result = DeleteParent($db, $log, $childId, $isFather, $userId);
		echo $result;
    });

    //*********************************  DOGS Entry Book  ********************************************************

    // Get dogs entry book
    $app->get('/dogsentrybook', $referer($app), $authorization($app), function() use ($app)
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $scope = $app->jwt->scope;
        $access = GetAccess($scope, $app->pages['sites']['entrybook'], $db, $log);
        $data = GetDogsEntryBook($db, $log);
        echo '{"access":"' . $access .'", "data":' .$data .'}';
    });

    //GET breeds autocomplete
	$app->get('/breedsautocompletedogsentrybook/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$breeds = GetBreedsAutoCompleteFromCommon($db, $filter);
		echo $breeds;
    });
    
    //GET colors autocomplete
	$app->get('/colorsautocompletedogsentrybook/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$colors = GetColorsAutoCompleteFromCommon($db, $filter);
		echo $colors;
    });

    //GET persons autocomplete
	$app->get('/personsautocompletedogsentrybook/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$persons = GetPersonsAutoCompleteFromCommon($db, $filter);
		echo $persons;
    });

     //GET dog autocomplete
	$app->get('/dogsautocompletedogsentrybook/:filter/:sex', $referer($app), $writeAccess($app), function ($filter, $sex) use ($app) 
	{
		$db = $app->db;			
		$dogs = GetDogsAutoCompleteFromCommon($db, $filter, $sex);
		echo $dogs;
    });

    //GET info about dog - entry book
    $app->get('/dogsentrybook/:id', $referer($app), $authorization($app), function($id) use ($app)
    {   
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $data = GetDogById($db, $log, $dbw, $id);
        echo $data;
    });

    //POST new dog - entrybook
    $app->post('/dogsentrybook', $referer($app), $writeAccess($app), function () use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        if (DogExists($db, $data)) {
            $app->response->status(409);
            echo "dog_exists";
            return;
        }
        if ($data->nrPedigree != '') {
            if (PedExists($db, $data)) {
                $app->response->status(409);
                echo "ped_exists";
                return;
            }
        }
        
        $result = AddDog($data, $db, $log, $userId, true);
        echo $result;
    });

 
    $app->run();
 