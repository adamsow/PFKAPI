<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';
require_once('ValidateLitterService.php');

function GetAllLitters($db, $log)
{
	$log -> addInfo("Getting all litters.");
	$stmt = $db->prepare("SELECT m.nr_miot as Id, h.przydomek as nickname, m.suka as mother, m.pies as father, m.ilosc as quantity, 
						m.data_ur as birthDate, m.poprawnosc as accepted
						FROM miot m
						JOIN hodowla h on h.nr_hod = m.przydomek;");
	
	$stmt->execute();
	$litters = $stmt->fetchAll();
	
	return json_encode($litters);
}

function GetLitterById($db, $log, $dbw, $id)
{
    $log -> addInfo("Getting info about litter: " . $id);	
	$stmt = $db->prepare("SELECT m.data_zgl as submissionDate, m.ilosc as quantity, m.ilosc_suk as bitchQuantity, m.data_kry as copulationDate, m.data_ur as birthDate, 
						m.suka as mother, m.pies as father, r.id_rasa as breedId, r.rasa as breed, h.przydomek as nickname, h.nr_hod as breedingId, cz.nr_leg as ownerId, 
						cz.przynaleznosc as ownerDepartment, CONCAT(o.imie, ' ', o.nazwisko) as ownerName, m.adnotacje as additionalInfo, m.poprawnosc as accepted,
						(select id_pies from pies where upper(fullname) = upper(m.pies) and plec = 'pies') as fatherId,
						(select id_pies from pies where upper(fullname) = upper(m.suka) and plec = 'suka') as motherId,
						m.creator as created_by, m.created, m.changed as modified, m.changed_by
						FROM miot m
						LEFT JOIN rasa r on r.id_rasa = m.rasa
						JOIN hodowla h on h.nr_hod = m.przydomek
						JOIN czlonek_hodowla czh on czh.nr_hod = h.nr_hod
						JOIN czlonek cz on cz.nr_leg = czh.nr_leg
						JOIN osoba o on o.czlonek = cz.nr_leg
						WHERE nr_miot = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$litter = $stmt->fetch();

	$createdBy = GetUser($litter['created_by'], $dbw);
	$modifiedBy = GetUser($litter['changed_by'], $dbw);
	$litter['created_by'] = $createdBy;
	$litter['changed_by'] = $modifiedBy;
	
	$litter = json_encode($litter);
	
	return $litter;
}

function LitterExists($db, $data)
{
    $stmt = $db->prepare("SELECT count(*)
						FROM miot 
						WHERE data_ur = :birthDate and ilosc = :quantity and pies =:father and suka = :mother;");
	
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':quantity', $data->quantity);
	$stmt->bindParam(':father', $data->father);
	$stmt->bindParam(':mother', $data->mother);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function LitterExistsWithAnotherId($db, $data, $id)
{
    $stmt = $db->prepare("SELECT count(*)
						FROM miot 
						WHERE data_ur = :birthDate and ilosc = :quantity and pies =:father and suka = :mother and nr_miot <> :id;");
	
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':quantity', $data->quantity);
	$stmt->bindParam(':father', $data->father);
	$stmt->bindParam(':mother', $data->mother);
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function AddLitter($data, $db, $log, $userId)
{
	if(!checkLitterData($data))
		return 0;

	$log -> addInfo("Adding litter: " . $data->birthDate);	

	$stmt = $db->prepare("INSERT INTO miot (data_zgl, data_kry, data_ur, ilosc, ilosc_suk, pies, suka, rasa, przydomek, adnotacje, 
						poprawnosc, creator, created, changed_by, changed) 
						VALUES (CURDATE(), :copulationDate, :birthDate, :quantity, :bitchQuantity, :father, :mother, :breed, :nickname, 
						:additionalInfo, :accepted, :userId, NOW(), :userId, NOW());");

	$stmt->bindParam(':copulationDate', $data->copulationDate);
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':quantity', $data->quantity);
	$stmt->bindParam(':bitchQuantity', $data->bitchQuantity);
	$stmt->bindParam(':father', $data->father);
	$stmt->bindParam(':mother', $data->mother);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->bindParam(':additionalInfo', $data->additionalInfo);
	$stmt->bindParam(':accepted', $data->accepted);
	$stmt->bindParam(':userId', $userId);

	$stmt->execute();

	$id = $db->lastInsertId();

	return $id;
}
function UpdateLitter($data, $db, $log, $userId, $id)
{
    if (!checkLitterData($data)) {
		return 0;
	}
    $log -> addInfo("Updating litter: " .  $id);	

	$stmt = $db->prepare("UPDATE miot set data_kry = :copulationDate, data_ur = :birthDate, ilosc = :quantity, ilosc_suk = :bitchQuantity, 
						pies = :father, suka = :mother, rasa = :breed, przydomek = :nickname, adnotacje = :additionalInfo, 
						poprawnosc = :accepted, changed_by = :userId, changed = NOW()
						WHERE nr_miot = :id;");

	$stmt->bindParam(':copulationDate', $data->copulationDate);
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':quantity', $data->quantity);
	$stmt->bindParam(':bitchQuantity', $data->bitchQuantity);
	$stmt->bindParam(':father', $data->father);
	$stmt->bindParam(':mother', $data->mother);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->bindParam(':additionalInfo', $data->additionalInfo);
	$stmt->bindParam(':accepted', $data->accepted);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':id', $id);

	$stmt->execute();

    return $id;
}

function RemoveLitter($id, $db, $log, $userId)
{
    $log -> addInfo("Removing litter: " . $id . " by " . $userId);

	$stmt = $db->prepare("DELETE FROM miot WHERE nr_miot = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	return true;
}

function GetBreedsAutoCompleteFromCommon($db, $filter)
{
	return GetBreedsAutoComplete($db, $filter);
}

function GetBreedingsAutoCompleteFromCommon($db, $filter)
{
	return GetBreedingsAutoComplete($db, $filter);
}

function GetDogsAutoCompleteFromCommon($db, $filter, $sex)
{
	return GetDogsAutoComplete($db, $filter, $sex);
}