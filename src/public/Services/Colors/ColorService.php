<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';
require_once('ValidateColorService.php');

function GetAllColors($db, $log)
{
	$log -> addInfo("Getting colors.");
	$colors = GetColors($db);
	
	return json_encode($colors);
}

function GetColorById($db, $log, $id, $dbw)
{
    $log -> addInfo("Getting info about color: " . $id);	
	$stmt = $db->prepare("SELECT masc as color_pl, colour as color, creator as created_by,
                        created, changed as modified, changed_by
                        FROM masc 
                        WHERE id_masc = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$color = $stmt->fetch();

	$createdBy = GetUser($color['created_by'], $dbw);
	$modifiedBy = GetUser($color['changed_by'], $dbw);
	$color['created_by'] = $createdBy;
	$color['changed_by'] = $modifiedBy;
	
	$color = json_encode($color);
	
	return $color;
}

function ColorExists($db, $data)
{
    $stmt = $db->prepare("SELECT count(*) 
						FROM masc 
						WHERE masc = :color_pl and colour = :color");
	
	$stmt->bindParam(':color_pl', $data->color_pl);
	$stmt->bindParam(':color', $data->color);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function GetExistingColorId($db, $data)
{
    $stmt = $db->prepare("SELECT id_masc as Id
						FROM masc 
						WHERE masc = :color_pl and colour = :color");
	
	$stmt->bindParam(':color_pl', $data->color_pl);
	$stmt->bindParam(':color', $data->color);
	$stmt->execute();

	$colorId = $stmt->fetch(); 

	return $colorId['Id'];
}

function AddColor($data, $db, $log, $userId)
{
	if(!checkColorData($data))
		return 0;

	$log -> addInfo("Adding color: " . $data->color_pl);	

	$stmt = $db->prepare("INSERT INTO masc (masc, colour, creator, created, changed_by, changed) 
					      VALUES (:color_pl, :color, :userId, NOW(), :userId, NOW());
						  ");

	$stmt->bindParam(':color_pl', $data->color_pl);
	$stmt->bindParam(':color', $data->color);
	$stmt->bindParam(':userId', $userId);

	$stmt->execute();

	$id = $db->lastInsertId();

	return $id;
}

function UpdateColor($data, $db, $log, $userId, $id)
{
    if (!checkColorData($data)) {
		return 0;
	}

	$stmt = $db->prepare("UPDATE masc set masc = :color_pl, colour = :color, changed_by = :userId, changed = NOW()
						WHERE id_masc = :id;");

	$stmt->bindParam(':color_pl', $data->color_pl);
	$stmt->bindParam(':color', $data->color);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':id', $id);

	$stmt->execute();

    return $id;
}

function RemoveColor($id, $db, $log, $userId)
{
    $log -> addInfo("Removing color: " . $id . " by " . $userId);

	$stmt = $db->prepare("DELETE FROM masc WHERE id_masc = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	return true;
}