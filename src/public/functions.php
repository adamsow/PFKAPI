<?php
use \Firebase\JWT\JWT;

function hello($db, $log, $dbw)
{
	$log -> addInfo("Something interesting happened");
	foreach($dbw->query('SELECT * from wp_users') as $row) 
	{
		print_r($row);
	}
}

function GetToken($username, $password, $dbw, $log, $secret)
{
	require_once('wp-functions.php');
	require_once('../vendor/firebase/php-jwt/src/JWT.php');
	require_once('../vendor/tuupola/slim-jwt-auth/src/JwtAuthentication.php');

	$log -> addInfo("Getting token for user: " . $username);
	$stmt = $dbw->prepare("SELECT wpu.user_pass, wpu.id, wpum.meta_value from wp_users wpu join wp_usermeta wpum on wpum.user_id = wpu.id where (wpu.user_nicename=:username or wpu.user_email=:username) and meta_key ='wp_capabilities'");
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
	$payload = array(
		"username" => $username,
		"user_id" => $user['id'],
		"jti" => $jti,
		"iat" => $now->getTimeStamp(),
		"exp" => $future->getTimeStamp(),
		"iss" => "https://c-pfk.pl",
		"aud" => "https://pfk.org.pl",
		"scope" => array_keys($roles)
	);
		
	$token = JWT::encode($payload, $secret, "HS256");
	$log -> addInfo("Token for user: " . $username . " created succesfuly. Generated token: " . $token);
	
	return $token;
}
