<?php
function GetAccess($scope, $pageName, $db)
{
	$in = join("','",$scope); 
	$stmt = $db->prepare("SELECT MAX(AccessLevelId) as ID, (Select Name from AccessLevel where ID = MAX(AccessLevelId)) as Name
						FROM AccessRights  
						WHERE APISiteID = (SELECT ID FROM APISites WHERE PageName = :pageName) 
						AND RoleName IN ('" . $in . "');");
	$stmt->bindParam(':pageName', $pageName);
	$stmt->execute();
	
	$access = $stmt->fetch();
	return $access['Name'];	
}

function HasWriteAccess($access)
{
	if($access === "WRITE" || $access === "ALL")
		return true;
	
	return false;
}

function HasAllAccess($access)
{
	if($access === "ALL")
		return true;
	
	return false;
}

function GetPage($accessPage, $sites)
{
	$pattern = $accessPage->pattern;
	if (strpos($pattern, '/member' ) > -1) {
		$department = $accessPage->params['department'];
		$page = $sites['sites']['members/' . $department];
	}
	else
	{
		$page = $sites['sites'][$pattern];
	}

	return $page;
}