<?php
	require_once('../APIConfiguration.php');
	require_once('../Services/Members/MemberService.php');

	//GET department members 
	$app->get('/members/:department/:filter', $referer($app),  $authorization($app), function ($department, $filter) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$scope = $app->jwt->scope;
		$access = GetAccess($scope, $app->pages['sites']['members/' . $department], $db, $log);
		$members = GetDepartmentMembers($db, $log, $department, $filter);
		echo '{"access":"' . $access . '","members":' . $members . '}';
	});

	//GET info about member
	$app->get('/member/:department/:id', $referer($app), $authorization($app), function ($department, $id) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$dbw = $app->dbw;
		$member = GetMemberById($db, $log, $id, $dbw);
		echo $member;
	});

	//POST new member
	$app->post('/members/:department', $referer($app), $writeAccess($app), function ($department) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
		if(UserExists($data->email))
		{
			$app->response->status(409);
			echo "user_exists";
			return;
		}
		if (PersonExists($db, $data)) {
			$app->response->status(409);
			echo "member_exists";
			return;
		}
		
       	$result = AddMember($data, $db, $log, $userId, $dbw);
       	echo $result;
	});

	//PUT udpate member
	$app->put('/members/:department/:id', $referer($app), $writeAccess($app), function ($department, $id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
		$dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
		$oldData = GetMemberById($db, $log, $id, $dbw);
		$oldData = json_decode($oldData);
		$changeEmail = false;
		$oldEmail = '';
		if ($oldData->email !== $data->email) {
			if(UserExists($data->email)){
				$app->response->status(409);
				echo "user_exists";
				return;
			}
			$changeEmail = true;
			$oldEmail = $oldData->email;
		}
		if ($oldData->surname !== $data->surname
			|| $oldData->name !== $data->name
			|| $oldData->city !== $data->city) {
			if (PersonExists($db, $data)) {
				$app->response->status(409);
				echo "member_exists";
				return;
			}
		}
		
       	$result = UpdateMember($data, $db, $log, $userId, $id, $changeEmail, $oldEmail);
       	echo $result;
	});

	//DELETE delete member
	$app->delete('/members/:department/:id', $referer($app), $allAccess($app), function ($department, $id) use ($app) 
	{
		$log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $scope = $app->jwt->scope;
        $userId = $app->jwt->user_id;
		$member = GetMemberById($db, $log, $id, $dbw);
		$member = json_decode($member);
        $result = RemoveMember($id, $db, $log, $userId, $member->email);
        echo $result;
	});

	//POST photo
	$app->post('/members/photo/:department/:id', $referer($app), $writeAccess($app), function ($department, $id) use ($app) 
	{
        $db = $app->db;
		if (CheckFile()) {
			SavePhoto($db, $id);
		}
	});

	//GET member consts
	$app->get('/membersConsts/:department', $referer($app), $authorization($app), function ($department) use ($app) 
	{
		$log = $app->log;
		$db = $app->db;
		$scope = $app->jwt->scope;
		$consts = GetMemberConsts($db, $log);
		echo $consts;
	});
	
	$app->run();