<?php

	$username = $_GET['apiuser'];
	$password = $_GET['password'];
	$message = $_GET['MsgId'];
	$status = $_GET['status'];
	$status_name = $_GET['status_name'];
	$to = $_GET['to'];
	$donedate = $_GET['donedate'];
	
	if($username == '' || $password == ''){
		echo 'OK';
		return;
	}

	$url = 'https://pfk.org.pl/PFKAPI/src/public/Controllers/AccountController.php/token/' . $username . '/' . $password;

	// create curl resource 
	$ch = curl_init(); 

	// set url 
	curl_setopt($ch, CURLOPT_URL, 'https://pfk.org.pl/PFKAPI/src/public/Controllers/AccountController.php/token/' . $username . '/' . $password); 

	//return the transfer as a string 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_REFERER, 'https://pfk.org.pl'); 

	// $output contains the output string 
	$output = json_decode(curl_exec($ch)); 

	// close curl resource to free up system resources 
	curl_close($ch);    

	$ch = curl_init(); 

	$headers = [
		"Content-type" => "application/x-www-form-urlencoded",
		"Authorization" => "Authorization: Bearer " . $output
	];
	$fields = array(
		'messagesIds' => $message,
		'statuses' => $status,
		'statuses_names' => $status_name,
		'to' => $to,
		'date' => $donedate,
	);

	$post_params = http_build_query($fields);

	curl_setopt($ch, CURLOPT_URL, 'https://pfk.org.pl/PFKAPI/src/public/Controllers/MassSMSController.php/smscallback'); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_REFERER, 'https://pfk.org.pl'); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
	$output = curl_exec($ch); 
	curl_close($ch);    
		
	echo $output;