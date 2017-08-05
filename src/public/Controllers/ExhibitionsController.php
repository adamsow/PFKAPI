<?php
    require_once('../APIConfiguration.php');
    require_once('../Services/Exhibitions/ExhibitionService.php');
    $pages = include __DIR__ . '/../pages.php';

    //GET exhibitions data 
	$app->get('/exhibitions/:filter', $referer($app), $authorization($app), function ($filter) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $scope = $app->jwt->scope;
        $access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
        $exhibitions = GetExhibitions($db, $log, $filter);
        echo '{"access":"' . $access . '","exhibitions":' . $exhibitions . '}';
	});

    //GET exhibition by ID 
	$app->get('/exhibitionById/:id', $referer($app), $authorization($app), function ($id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $exhibition = GetExhibitionById($db, $log, $id, $dbw);
        echo $exhibition;
	});
	
	//Insert new exhibition 
	$app->post('/exhibitions', $referer($app), $writeAccess($app), function () use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        $result = AddExhibition($data, $db, $log, $userId);
        echo $result;
	});
	
	//Update existing exhibition 
	$app->put('/exhibitions', $referer($app), $writeAccess($app), function () use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        $result = UpdateExhibition($data, $db, $log, $userId);
        echo $result;
	});
	
	//Delete exhibition 
	$app->delete('/exhibitions/:id', $referer($app), $allAccess($app), function ($id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        $result = RemoveExhibition($id, $db, $log, $userId);
        echo $result;
	});
	
	//GET departments 
	$app->get('/departments', $referer($app), $writeAccess($app), function () use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $departments = GetDepartments($db, $log);
        echo $departments;
	});

$app->run();