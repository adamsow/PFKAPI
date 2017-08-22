<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';
require_once('ValidateBreedingService.php');

function GetAllBreedings($db, $log, $filter)
{
	$log -> addInfo("Getting breedings.");
	switch($filter)
	{
		case "all":
			$condition = " 1 = 1";
			break;
		case "active":
			$condition = " data_wrej is null or data_wrej = ''";
			break;
		case "not-active":
			$condition = " data_wrej is not null and data_wrej <> ''";
			break;
		default:
			$condition = ' 1 = 1';
			break;
	}
	$stmt = $db->prepare("SELECT  h.nr_hod as Id, h.przydomek as nickname, h.poprawnosc as accepted,
            			GROUP_CONCAT(CONCAT(o.imie, ' ', o.nazwisko) SEPARATOR ', ') as owner,
						GROUP_CONCAT(o.miejscowosc SEPARATOR ', ') as city
						FROM hodowla h
						JOIN czlonek_hodowla czh ON czh.nr_hod = h.nr_hod
						JOIN czlonek cz on cz.nr_leg = czh.nr_leg
						JOIN osoba o on o.czlonek = cz.nr_leg
						WHERE " . $condition . "
                        GROUP BY h.nr_hod
						Order BY h.nr_hod;");
	$stmt->execute();
	$breedings = $stmt->fetchAll();
	
	return json_encode($breedings);
}

function getBreedingById($db, $log, $id, $dbw)
{
    $log -> addInfo("Getting info about breeding: " . $id);	
		$stmt = $db->prepare("SELECT h.przydomek as nickname, h.pisany as written, h.data_rej as registrationDate, h.data_wrej as unregisterDate,
							h.email, h.www as website, h.opis as characteristic, h.adnotacje as additionalInfo, h.poprawnosc as accepted,
							h.creator as created_by, h.created, h.changed as modified, h.changed_by, h.www_widoczne as showWWW,
							h.adres as address
							FROM hodowla h
							JOIN czlonek_hodowla czh on czh.nr_hod = h.nr_hod
							JOIN czlonek cz on cz.nr_leg = czh.nr_leg
							JOIN osoba o on o.czlonek = cz.nr_leg
							WHERE h.nr_hod = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$breeding = $stmt->fetch();

	$createdBy = GetUser($breeding['created_by'], $dbw);
	$modifiedBy = GetUser($breeding['changed_by'], $dbw);
	$breeding['created_by'] = $createdBy;
	$breeding['changed_by'] = $modifiedBy;

	//get breeds
	$stmt = $db->prepare("SELECT r.rasa as breed, r.id_rasa as breedId 
						  FROM rasa_hodowla rh 
						  JOIN rasa r on rh.id_rasa = r.id_rasa 
						  WHERE rh.nr_hod = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$breeds = $stmt->fetchAll();
	$breeding['breeds'] = $breeds;

	//get owners
	$stmt = $db->prepare("SELECT CONCAT(o.imie, ' ', o.nazwisko) as owner, cz.przynaleznosc as department, cz.nr_leg as ownerId
						  FROM czlonek_hodowla czh
						  JOIN czlonek cz on cz.nr_leg = czh.nr_leg
						  JOIN osoba o on o.czlonek = czh.nr_leg
						  WHERE czh.nr_hod = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$breeds = $stmt->fetchAll();
	$breeding['owners'] = $breeds;
	
	$breeding = json_encode($breeding);
	
	return $breeding;
}

function BreedingExists($db, $data)
{
    $stmt = $db->prepare("SELECT count(*) 
						FROM hodowla 
						WHERE przydomek = :nickname;");
	
	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function BreedingExistsWithAnotherId($db, $data, $id)
{
    $stmt = $db->prepare("SELECT count(*)
						FROM hodowla 
						WHERE przydomek = :nickname and nr_hod <> :id;");
	
	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function AddBreeding($data, $db, $log, $userId, $dbw)
{
	if(!checkBreedingData($data))
		return 0;

	$log -> addInfo("Adding breeding: " . $data->nickname);	

	//add breeding
	$stmt = $db->prepare("INSERT INTO hodowla (przydomek, pisany, data_rej, adres, email, www, opis, adnotacje, creator, created, 
						changed, changed_by, www_widoczne) 
						VALUES (:nickname, :written, CURDATE(),:address,:email,:website,:characteristic,:additionalInfo, 
						:userId, NOW(), NOW(), :userId, :showWWW);");

	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->bindParam(':written', $data->written);
	$stmt->bindParam(':address', $data->address);
	$stmt->bindParam(':email', $data->email);
	$stmt->bindParam(':website', $data->website);
	$stmt->bindParam(':characteristic', $data->characteristic);
	$stmt->bindParam(':additionalInfo', $data->additionalInfo);
	$stmt->bindParam(':showWWW', $data->showWWW);
	$stmt->bindParam(':userId', $userId);
	$stmt->execute();

	$id = $db->lastInsertId();

	CreateBreedingMemberConnection($db, $id, $data);
	CreateBreedingBreedConnection($db, $id, $data);

	return $id;
}

function UpdateBreeding($data, $db, $log, $userId, $id)
{
    if (!checkBreedingData($data)) {
		return 0;
	}

	$stmt = $db->prepare("UPDATE hodowla set przydomek = :nickname, pisany = :written, www = :website,
						opis = :characteristic, adnotacje = :additionalInfo, data_wrej = :unregisterDate, poprawnosc = :accepted,
						changed_by = :userId, changed = NOW(), www_widoczne = :showWWW
						WHERE nr_hod = :id;");

	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->bindParam(':written', $data->written);
	//$stmt->bindParam(':address', $data->address);
	//$stmt->bindParam(':email', $data->email);
	$stmt->bindParam(':website', $data->website);
	$stmt->bindParam(':characteristic', $data->characteristic);
	$stmt->bindParam(':additionalInfo', $data->additionalInfo);
	$stmt->bindParam(':unregisterDate', $data->unregisterDate);
	$stmt->bindParam(':accepted', $data->accepted);
	$stmt->bindParam(':showWWW', $data->showWWW);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	DeleteBreedingMemberConnection($db, $id);
	DeleteBreedingBreedConnection($db, $id);
	CreateBreedingMemberConnection($db, $id, $data);
	CreateBreedingBreedConnection($db, $id, $data);

    return $id;
}

function RemoveBreeding($id, $db, $log, $userId)
{
    $log -> addInfo("Removing breeding: " . $id . " by " . $userId);
	
	DeleteBreedingMemberConnection($db, $id);
	DeleteBreedingBreedConnection($db, $id);

	$stmt = $db->prepare("DELETE FROM hodowla WHERE nr_hod = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	return true;
}

function CreateBreedingMemberConnection($db, $id, $data)
{
	//add breeding - owner connection
	foreach ($data->owners as $ownerId) {
		$stmt = $db->prepare("INSERT INTO czlonek_hodowla (nr_leg, nr_hod) VALUES (:ownerId, :breedingId);");
		$stmt->bindParam(':ownerId', $ownerId);
		$stmt->bindParam(':breedingId', $id);
		$stmt->execute();
	}
}

function CreateBreedingBreedConnection($db, $id, $data)
{
	//add breeding - breeds connection
	foreach ($data->breeds as $breedId) {
		$stmt = $db->prepare("INSERT INTO rasa_hodowla (id_rasa, nr_hod) VALUES (:breedId, :breedingId);");
		$stmt->bindParam(':breedId', $breedId);
		$stmt->bindParam(':breedingId', $id);
		$stmt->execute();
	}
}

function DeleteBreedingMemberConnection($db, $id)
{
	$stmt = $db->prepare("DELETE FROM czlonek_hodowla WHERE nr_hod = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
}

function DeleteBreedingBreedConnection($db, $id)
{
	$stmt = $db->prepare("DELETE FROM rasa_hodowla WHERE nr_hod = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
}

function GetBreedsAutoCompleteFromCommon($db, $filter)
{
	return GetBreedsAutoComplete($db, $filter);
}