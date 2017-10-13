<?php
	require_once('../APIConfiguration.php');
    require_once('../Services/SingleMember/SingleMemberService.php');
	require_once('../Services/Members/MemberService.php');
    
	//GET my details 
	$app->get('/mydetails', $referer($app), $authorization($app), function () use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
        $scope = $app->jwt->scope;
        $memberId = $app->jwt->member_id;
        if ($memberId == null) {
            return $app->response->write(json_encode(false));
        }
		$myDetails = GetMyDetails($db, $memberId, $log);
		echo $myDetails;
	});

	//PUT my details
	$app->put('/updatemydetails', $referer($app), $authorization($app), function () use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
		$dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $memberId = $app->jwt->member_id;
        $data = json_decode($body);
		$oldData = GetMemberById($db, $log, $memberId, $dbw);
        $oldData = json_decode($oldData);
        $oldEmail = '';
        $changeEmail = false;
		if ($oldData->email !== $data->email) {
			if(UserExists($data->email)){
				$app->response->status(409);
				echo "user_exists";
				return;
			}
			$changeEmail = true;
			$oldEmail = $oldData->email;
		}
		
       	$result = UpdateMyDetails($data, $db, $log, $userId, $memberId, $changeEmail, $oldEmail);
       	echo $result;
	});

    //GET download certificate for member
	$app->get('/mydetailscertificate/:token/:fullname',  $getTokenFromParams($app), $referer($app),  $authorization($app), function ($token, $fullname) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
        $dbw = $app->dbw;
        $memberId = $app->jwt->member_id;
		$app->response->headers->set('Content-Type', 'application/pdf');
		$app->response->write( GetCertificate($db, $log, $memberId, $dbw, false) );
		
		return $app->response;
	});

	//GET download certificate for breeder
	$app->get('/mybreedingscertificate/:token/:fullname',  $getTokenFromParams($app), $referer($app),  $authorization($app), function ($token, $fullname) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$dbw = $app->dbw;
		$memberId = $app->jwt->member_id;
		$app->response->headers->set('Content-Type', 'application/pdf');
		$app->response->write( GetCertificate($db, $log, $memberId, $dbw, true) );
		
		return $app->response;
	});
	
	//GET my breedings 
	$app->get('/mybreedings', $referer($app), $authorization($app), function () use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$scope = $app->jwt->scope;
		$memberId = $app->jwt->member_id;
		if ($memberId == null) {
			return $app->response->write(json_encode(false));
		}
		$myBreedings = GetMyBreedings($db, $memberId, $log);
		echo $myBreedings;
	});

	//PUT update my breedings
	$app->put('/updatemybreedings', $referer($app), $authorization($app), function () use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
		$dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $memberId = $app->jwt->member_id;
        $data = json_decode($body);
		
       	$result = UpdateMyBreedings($data, $db, $log, $userId, $memberId);
       	echo $result;
	});
	
	//GET breed autocomplete
	$app->get('/breedsautocompletemybreedings/:filter', $referer($app), $authorization($app), function ($filter) use ($app) 
	{
		$db = $app->db;			
		$breeds = GetBreedsAutoCompleteFromCommon($db, $filter);
		echo $breeds;
	});
	
	//GET my dogs 
	$app->get('/mydogs', $referer($app), $authorization($app), function () use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$scope = $app->jwt->scope;
		$memberId = $app->jwt->member_id;
		if ($memberId == null) {
			return $app->response->write(json_encode(false));
		}
		$myDogs = GetMyDogs($db, $memberId, $log);
		echo $myDogs;
	});

	//DELETE my dog 
	$app->delete('/mydogs/:id', $referer($app), $authorization($app), function ($id) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$scope = $app->jwt->scope;
		$memberId = $app->jwt->member_id;
		if ($memberId == null) {
			return $app->response->write(json_encode(false));
		}
		DeleteMyDog($db, $memberId, $log, $id);
	});

	$app->run();