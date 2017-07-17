<?php
function checkFullname($fullname) {
		error_reporting(E_ALL);
	$fullname = replaceChars($fullname);
	if(!preg_match("/^[ A-Za-z&ĘÓĽŁŻŃĆęóšłżćńÁÂÄÇÉËÔÖÓÜÚÝÜÝßâäáäăçëéÍÎíîôöőóúüůűý.,\'-]{2,50}$/", $fullname)) 
		return false; 
	if($fullname[0]=="-" || $fullname[strlen($fullname)-1]=="-") 
		return false; 
	
	return true;
}

function checkBirthDate($birthDate) {
	if(!preg_match ("/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/", $birthDate)) 
		return false;

	list($year,$month,$day)=explode('-',$birthDate);
	$today = getdate();
	$dm = $today['mon']; $dd = $today['mday']; $dr = $today['year'];
	if($dr<$year) 
		return false;
	if($dr==$year && $dm<$month) 
		return false;
	if($dr==$year && $dm==$month && $dd<$day) 
		return false;
	
	return checkdate($month,$day,$year);
}

function checkTitle($title) {
	$title = replaceChars($title);
	if(!preg_match("/^[ A-Za-zĘÓĽŁŻŃĆęóšłżćńÁÂÄÇÉËÔÖÓÜÚÝÜÝßâäáäăçëéÍÎíîôöőóúüůűý.,-]{4,50}$/", $title)) 
		return false; 
	
	return true;
}


function checkTraining($training) {
	if(!preg_match("/^[ A-Za-z0-9.:,\/-]{3,50}$/", $training)) 
		return false;
	
	return true;
}

function checkLineage($lineage) {
	if(!preg_match("/^[ A-Za-z0-9.:()\/-]{3,30}$/", $lineage)) 
		return false; 
	
	return true;
}

function checkMarking($marking) {
	if(!preg_match("/^[ A-Za-z0-9.:\/-]{3,25}$/", $marking)) 
		return false; 
	
	return true;
}

function checkName($name) {
	$name = replaceChars($name);
	if(!preg_match("/^[ A-Za-zĘÓĽŁŻŃĆęóšłżćńÁÂÄÇÉËÔÖÓÜÚÝÜÝßâäáäăçëéÍÎíîôöőóúüůűý.]{2,50}$/", $name)) 
		return false; 
	
	return true;
}

function checkStreet($address) {
	$address = replaceChars($address);
	$start = array('.', '-', '/');
	$end = array('.', '-', '/');
	if(!preg_match("/^[ A-Za-zĘÓĽŁŻŃĆęóšłżćńÁÂÄÇÉËÔÖÓÜÚÝÜÝßâäáäăçëéÍÎíîôöőóúüůűý0-9.,\/-]{4,50}$/", $address)) 
		return false; 
	
	for($i=0; $i<count($start); $i++) 
	{
		if($address[0]==$start[$i]) 
			return false;
	}
		
	for($i=0; $i<count($end); $i++) 
	{
		if($address[strlen($address)-1]==$end[$i]) 
			return false;
	}
	
	return true;
}

function checkPostal($postal) {
	if(!preg_match("/^[ A-Z0-9.\/-]{4,20}$/", $postal)) 
		return false; 
	
	return true;
}

function checkCity($city) {
	$city = replaceChars($city);
	if(!preg_match("/^[ A-Za-zĘÓĽŁŻŃĆęóšłżćńÁÂÄÇÉËÔÖÓÜÚÝÜÝßâäáäăçëéÍÎíîôöőóúüůűý-]{2,50}$/", $city)) 
		return false; 
	
	if($city[0]=="-" or $city[strlen($city)-1]=="-") 
		return false; 
	
	return true;
}

function checkMobile($mobile) {
	if(!preg_match("/^[ 0-9wewlub().+#p*-]{7,50}$/", $mobile)) 
		return false; 
	
	if($mobile[0]=="-" 
		|| $mobile[0]=="w" 
		|| $mobile[0]=="." 
		|| $mobile[0]=="p" 
		|| $mobile[0]=="#" 
		|| $mobile[0]=="*" 
		|| $mobile[strlen($mobile)-1]=="-" 
		|| $mobile[strlen($mobile)-1]=="y" 
		|| $mobile[strlen($mobile)-1]=="w" 
		|| $mobile[strlen($mobile)-1]=="." 
		|| $mobile[strlen($mobile)-1]=="p" 
		|| $mobile[strlen($mobile)-1]=="#" 
		|| $mobile[strlen($mobile)-1]=="*")
			return false; 
			
	return true;
}

function checkEmail($email) {
	if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", $email)) 
		return false; 
	
	return true;
}

function replaceChars($word) {
	$locals = array("ą", "Ą", "ś", "Ś", "ź", "Ź",);
	$normal = array("a", "A", "s", "S", "z", "Z");
	$word = str_replace($locals, $normal, $word);
	
	return $word;
}