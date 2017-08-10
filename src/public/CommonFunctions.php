<?php
function GetAccess($scope, $pageName, $db)
{
	$in = join("','",$scope); 
	$stmt = $db->prepare("SELECT MAX(al.ID) as ID, al.Name 
						FROM AccessRights ar 
						JOIN AccessLevel al on al.ID = ar.AccessLevelID 
						WHERE APISiteID = (SELECT ID FROM APISites WHERE PageName = :pageName) 
						AND RoleName IN ('" . $in . "');");
	$stmt->bindParam(':pageName', $pageName);
	$stmt->execute();
	
	$access = $stmt->fetch();
	return $access['Name'];	
}

function HasWriteAccess($access)
{
	if($access === "ZAPIS" || $access === "ALL")
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