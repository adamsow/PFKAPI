<?php
use \Firebase\JWT\JWT;

function GetToken($username, $password, $dbw, $log, $secret)
{
	require_once('../wp-functions.php');
	require_once('../../vendor/firebase/php-jwt/src/JWT.php');
	require_once('../../vendor/tuupola/slim-jwt-auth/src/JwtAuthentication.php');

	$log -> addInfo("Getting token for user: " . $username);
	$stmt = $dbw->prepare("SELECT wpu.user_pass, wpu.id, wpum.meta_value 
		FROM wp_users wpu join wp_usermeta wpum on wpum.user_id = wpu.id 
		WHERE (wpu.user_nicename=:username OR wpu.user_email=:username) AND meta_key ='wp_capabilities'");
	$stmt->bindParam(':username', $username);
	$stmt->execute();
	
	$user = $stmt->fetch();
	if($user['user_pass'] === NULL)
		return false;

	if(wp_check_password($password, $user['user_pass']) === false)
		return false;
	
	$roles = unserialize(stripslashes($user['meta_value']));
	$now = new DateTime();
	$future = new DateTime("now +24 hours");
	$rand = substr(md5(microtime()),rand(0,26),5);
	$jti = base64_encode($rand);
	$payload = [
		"username" => $username,
		"user_id" => $user['id'],
		"jti" => $jti,
		"iat" => $now->getTimeStamp(),
		"exp" => $future->getTimeStamp(),
		"iss" => "https://c-pfk.pl",
		"aud" => "https://pfk.org.pl",
		"scope" => array_keys($roles)
	];
		
	$token = JWT::encode($payload, $secret, "HS256");
	$log -> addInfo("Token for user: " . $username . " created succesfuly. Generated token: " . $token);
	
	return $token;
}

function GetRoles($db, $log)
{
	$log -> addInfo("Getting roles.");
	$stmt = $db->prepare("SELECT option_value FROM wp_options WHERE option_name = 'wp_user_roles';");
	$stmt->execute();
	$roles = $stmt->fetch();
	
	$roles = unserialize(stripslashes($roles['option_value']));
	$roleKeys = array_keys($roles);

	$i = 0;
	foreach ($roles as $role) 
	{
		$roleKey = $roleKeys[$i];
		$roleNames[$roleKey] = $role['name'];
		$i++;
	}
	return json_encode($roleNames);
}

function GetApiSites($db, $log)
{
	$log -> addInfo("Getting api sites.");
	$stmt = $db->prepare("SELECT * FROM APISites;");
	$stmt->execute();
	$sites = $stmt->fetchAll();
	
	return json_encode($sites);
}

function GetRoleAssignments($db, $log)
{
	$log -> addInfo("Getting role assigments.");
	$stmt = $db->prepare("SELECT * FROM AccessRights;");
	$stmt->execute();
	$assigments = $stmt->fetchAll();
	
	return json_encode($assigments);
}

function SaveRoles($data, $db, $log)
{
	$log -> addInfo("Saving roles");
	$stmt = $db->prepare("DELETE FROM AccessRights;");
	$stmt->execute();
	foreach ($data as $role) 
	{
		$stmt = $db->prepare("INSERT INTO AccessRights (APISiteID, AccessLevelID, RoleName)
							VALUES (:apiSiteId, :accessLevelId, :roleName);");
		$stmt->bindParam(':apiSiteId', $role->ApiSiteID);
		$stmt->bindParam(':accessLevelId', $role->AccessLevel);
		$stmt->bindParam(':roleName', $role->Role);
		$stmt->execute();
	}
	
	return true;
}