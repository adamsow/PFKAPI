<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';
require_once('ValidateBreedService.php');

function GetAllBreeds($db, $log)
{
	$log -> addInfo("Getting breeds.");
	$breeds = GetBreeds($db);
	
	return json_encode($breeds);
}

function GetBreedById($db, $log, $id, $dbw)
{
    $log -> addInfo("Getting info about breed: " . $id);	
	$stmt = $db->prepare("SELECT rasa as breed_pl, breed, grupa as gr, creator as created_by,
                        created, changed as modified, changed_by
                        FROM rasa 
                        WHERE id_rasa = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$breed = $stmt->fetch();

	$createdBy = GetUser($breed['created_by'], $dbw);
	$modifiedBy = GetUser($breed['changed_by'], $dbw);
	$breed['created_by'] = $createdBy;
	$breed['changed_by'] = $modifiedBy;
	
	$breed = json_encode($breed);
	
	return $breed;
}

function BreedExists($db, $data)
{
    $stmt = $db->prepare("SELECT count(*) 
						FROM rasa 
						WHERE rasa = :breed_pl and breed = :breed");
	
	$stmt->bindParam(':breed_pl', $data->breed_pl);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function GetExistingBreedId($db, $data)
{
    $stmt = $db->prepare("SELECT id_rasa as Id
						FROM rasa 
						WHERE rasa = :breed_pl and breed = :breed");
	
	$stmt->bindParam(':breed_pl', $data->breed_pl);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->execute();

	$breedId = $stmt->fetch(); 

	return $breedId['Id'];
}

function AddBreed($data, $db, $log, $userId, $dbw)
{
	if(!checkBreedData($data))
		return 0;

	$log -> addInfo("Adding breed: " . $data->breed_pl);	

	$stmt = $db->prepare("INSERT INTO rasa (rasa, breed, grupa, creator, created, changed_by, changed) 
					      VALUES (:breed_pl, :breed, :group, :userId, NOW(), :userId, NOW());
						  ");

	$stmt->bindParam(':breed_pl', $data->breed_pl);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->bindParam(':group', $data->group);
	$stmt->bindParam(':userId', $userId);

	$stmt->execute();

	$id = $db->lastInsertId();

	return $id;
}

function UpdateBreed($data, $db, $log, $userId, $id)
{
    if (!checkBreedData($data)) {
		return 0;
	}

	$stmt = $db->prepare("UPDATE rasa set rasa = :breed_pl, breed = :breed, grupa = :group, changed_by = :userId, changed = NOW()
						WHERE id_rasa = :id;");

	$stmt->bindParam(':breed_pl', $data->breed_pl);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->bindParam(':group', $data->group);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':id', $id);

	$stmt->execute();

    return $id;
}

function RemoveBreed($id, $db, $log, $userId)
{
    $log -> addInfo("Removing breed: " . $id . " by " . $userId);

	$stmt = $db->prepare("DELETE FROM rasa WHERE id_rasa = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	return true;
}