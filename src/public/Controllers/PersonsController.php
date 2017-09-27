<?php
	require_once('../APIConfiguration.php');
	require_once('../Services/Persons/PersonService.php');

	//GET persons 
	$app->get('/persons', $referer($app),  $authorization($app), function () use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$scope = $app->jwt->scope;
		$access = GetAccess($scope, $app->pages['sites']['persons'], $db, $log);
		$persons = GetPersons($db, $log);
		echo '{"access":"' . $access . '","persons":' . $persons . '}';
	});

	//GET info about person
	$app->get('/persons/:id', $referer($app), $authorization($app), function ($id) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$dbw = $app->dbw;
		$person = GetPersonById($db, $log, $id, $dbw);
		echo $person;
	});

	//POST new person
	$app->post('/persons', $referer($app), $writeAccess($app), function () use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
		if (PersonExists($db, $data)) {
			$app->response->status(409);
			echo "person_exists";
			return;
		}
		
       	$result = AddPerson($data, $db, $log, $userId, $dbw);
       	echo $result;
	});

	//PUT udpate person
	$app->put('/persons/:id', $referer($app), $writeAccess($app), function ($id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
		$dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
		$oldData = GetPersonById($db, $log, $id, $dbw);
		$oldData = json_decode($oldData);
		if ($oldData->surname !== $data->surname
			|| $oldData->name !== $data->name
			|| $oldData->city !== $data->city) {
			if (PersonExists($db, $data)) {
				$app->response->status(409);
				echo "person_exists";
				return;
			}
		}
		
       	$result = UpdatePerson($data, $db, $log, $userId, $id);
       	echo $result;
	});

	//DELETE delete person
	$app->delete('/persons/:id', $referer($app), $allAccess($app), function ($id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $scope = $app->jwt->scope;
        $userId = $app->jwt->user_id;
        $result = RemovePerson($id, $db, $log, $userId);
        echo $result;
	});


	//GET persons consts
	$app->get('/personsConsts', $referer($app), $authorization($app), function () use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$scope = $app->jwt->scope;
		$consts = GetPersonConsts($db, $log);
		echo $consts;
	});
	
	$app->run();