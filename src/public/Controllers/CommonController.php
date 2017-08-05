<?php
require_once('../APIConfiguration.php');
require_once('../Services/Commons/CommonService.php');

//GET breedings
$app->get('/breedings', $referer($app), function () use ($app) 
{
	$log = $app->log;
	$db = $app->db;
	$breedings = GetBreedings($db, $log);
	echo $breedings;
});

//GET studs
$app->get('/studs', $referer($app), function () use ($app) 
{
	$log = $app->log;
	$db = $app->db;			
	$studs = GetStuds($db, $log);
	echo $studs;
});

//GET litters
$app->get('/litters', $referer($app), function () use ($app) 
{
	$log = $app->log;
	$db = $app->db;			
	$litters = GetLitters($db, $log);
	echo $litters;
});

//GET exhibition data for form
$app->get('/exhibition', $referer($app), function () use ($app) 
{
	$log = $app->log;
	$db = $app->db;			
	$exhibitionData = GetExhibitionData($db, $log);
	echo $exhibitionData;
});

//POST exhibition data form
$app->post('/exhibition', $referer($app), function () use ($app) 
{	
	$log = $app->log;
	$db = $app->db;			
	$payload = stripslashes($_POST["payload"]);
	$log -> addInfo($payload);
	$data = json_decode($payload);
	$result = SaveExhibitionData($db, $log, $data);
	$log -> addInfo($result);

	echo $result;
});

$app->run();