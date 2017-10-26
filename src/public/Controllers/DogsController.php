<?php
	require_once('../APIConfiguration.php');
    require_once('../Services/Dogs/DogsService.php');
    
    // Get DNA data
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
        $data = GetDNAById($db, $log, $dbw, $id);
        echo $data;
    });

    //POST new DNA
    $app->post('/dna', $referer($app), $writeAccess($app), function () use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        if (DNAExists($db, $data)) {
            $app->response->status(409);
            echo "DNA_exists";
            return;
        }
        
        $result = AddDNA($data, $db, $log, $userId);
        echo $result;
    });

    //PUT udpate DNA
    $app->put('/dna/:id', $referer($app), $writeAccess($app), function ($id) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        if (DNAExistsWithAnotherId($db, $data, $id)) {
            $app->response->status(409);
            echo "DNA_exists";
            return;
        }
        
            $result = UpdateDNA($data, $db, $log, $userId, $id);
            echo $result;
    });

    //DELETE delete DNA data
    $app->delete('/dna/:id', $referer($app), $allAccess($app), function ($id) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $userId = $app->jwt->user_id;
        $result = RemoveDNA($id, $db, $log, $userId);
        echo $result;
    });

    //GET dog autocomplete
	$app->get('/dogsautocompletedna/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$dogs = GetDogsAutoCompleteFromCommon($db, $filter);
		echo $dogs;
    });

    //GET get users with role DNA
	$app->get('/dnamembers', $referer($app), $authorization($app), function () use ($app) 
	{
        $log = $app->log;
        $db = $app->db;		
        $dbw = $app->dbw;
		$members = GetDNAMembers($db, $dbw, $log);
		echo $members;
    });

    //GET breedings autocomplete
	$app->get('/breedsautocompletedna/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$breeds = GetBreedsAutoCompleteFromCommon($db, $filter);
		echo $breeds;
    });
    
    //GET colors autocomplete
	$app->get('/colorsautocompletedna/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$colors = GetColorsAutoCompleteFromCommon($db, $filter);
		echo $colors;
    });

     //GET persons autocomplete
	$app->get('/personsautocompletedna/:filter', $referer($app), $writeAccess($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$persons = GetPersonsAutoCompleteFromCommon($db, $filter);
		echo $persons;
    });
 
    $app->run();
 