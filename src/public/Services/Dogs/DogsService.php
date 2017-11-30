<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';
require_once('ValidateDogsService.php');

function GetDogsWithLineages($db, $log)
{
	$log -> addInfo("Getting dogs with lineage.");
	$stmt = $db->prepare("SELECT p.id_pies as id, p.fullname as nickname, p.nr_kkr as lineage, p.poprawnosc as accepted, 
                        r.rasa as breed, p.plec as sex, CASE WHEN wp.id_pies is null THEN '' 
                        ELSE Concat(o.imie, ' ', o.nazwisko) END as owner
                        FROM pies p
                        JOIN rasa r on r.id_rasa = p.rasa
                        LEFT JOIN wlasciciel_pies wp on wp.id_pies = p.id_pies
                        LEFT JOIN osoba o on o.id_osoba = wp.id_osoba
                        WHERE p.nr_kkr is not null and p.nr_kkr <> '';");
	
	$stmt->execute();
    $dogsWithLineage = $stmt->fetchAll();
	
	return json_encode($dogsWithLineage);
}

function GetDogById($db, $log, $dbw, $id)
{
    $log -> addInfo("Getting info about dog: " . $id);	
	$stmt = $db->prepare("SELECT p.id_pies as id, p.fullname as nickname, p.nr_kkr as lineage, p.nr_kkw as kkw, 
                        r.id_rasa as breedId, r.rasa as breed, m.id_masc as colorId, m.masc as color, p.plec as sex,
						COALESCE(o.czlonek, o.id_osoba) as ownerId,
						COALESCE(oh.czlonek, oh.id_osoba) as breederId, 
						COALESCE(o.czlonek, 0) as isOwnerMember,
						COALESCE(oh.czlonek, 0) as isBreederMember, 
                        Concat(o.imie, ' ', o.nazwisko) as owner, Concat(oh.imie, ' ', oh.nazwisko) as breeder, 
                        p.data_ur as birthDate, p.tytul as titles, p.wyszkolenie as training, p.poprawnosc as accepted,
                        p.oznakowanie as marking, p.nr_ped as pedigree, p.hd, p.ed, p.dna, p.badanie_dna as dnaCheck, 
                        p.testy_psych as psychoTests, p.badania as otherChecks, p.nadane_upr as breedPermissions,
                        p.adnotacje as additionalInfo, p.creator as created_by, p.created, p.changed as modified, p.changed_by,
                        cz.przynaleznosc as ownerDepartment, czh.przynaleznosc as breederDepartment
                        FROM pies p
                        JOIN rasa r on r.id_rasa = p.rasa
                        JOIN masc m on m.id_masc = p.masc
                        LEFT JOIN wlasciciel_pies wp on wp.id_pies = p.id_pies
                        LEFT JOIN osoba o on o.id_osoba = wp.id_osoba
                        LEFT JOIN hodowca_pies hp on hp.id_pies = p.id_pies
                        LEFT JOIN osoba oh on oh.id_osoba = hp.id_osoba
                        LEFT JOIN czlonek cz on cz.nr_leg = o.czlonek
                        LEFT JOIN czlonek czh on czh.nr_leg = oh.czlonek
                        WHERE p.id_pies = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$dog = $stmt->fetch();

	$createdBy = GetUser($dog['created_by'], $dbw);
    $modifiedBy = GetUser($dog['changed_by'], $dbw);
	$dog['created_by'] = $createdBy;
    $dog['changed_by'] = $modifiedBy;
	
	$dog = json_encode($dog);
	
	return $dog;
}

function DogExists($db, $data)
{
    $stmt = $db->prepare("SELECT count(*) FROM pies WHERE fullname = :nickname AND plec = :sex AND rasa = :breed 
						AND data_ur = :birthDate AND nr_ped = :nrPedigree;");
	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->bindParam(':sex', $data->sex);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':nrPedigree', $data->nrPedigree);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function PedExists($db, $data)
{
	$stmt = $db->prepare("SELECT count(*) FROM pies WHERE nr_ped = :nrPedigree;");
	$stmt->bindParam(':nrPedigree', $data->nrPedigree);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;

	return false;
}

function DogExistsWithAnotherId($db, $data, $id)
{
	$stmt = $db->prepare("SELECT count(*) FROM pies WHERE fullname = :nickname AND plec = :sex AND rasa = :breed 
						AND data_ur = :birthDate AND nr_ped = :nrPedigree and id_pies <> :dogId;");
	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->bindParam(':sex', $data->sex);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':nrPedigree', $data->nrPedigree);
	$stmt->bindParam(':dogId', $id);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function PedExistsWithAnotherId($db, $data, $id)
{
	$stmt = $db->prepare("SELECT count(*) FROM pies WHERE nr_ped = :nrPedigree and id_pies <> :dogId;");
	$stmt->bindParam(':nrPedigree', $data->nrPedigree);
	$stmt->bindParam(':dogId', $id);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;

	return false;
}

function AddDog($data, $db, $log, $userId, $isKkw)
{
	if(!checkDogData($data))
		return 0;

	$log -> addInfo("Adding new dog: " . $data->nickname);	
	$lineageNr;
	if (!$isKkw) {
		$stmt = $db->prepare("SELECT count(*) FROM pies
							WHERE nr_kkr is not null and nr_kkr <> '';");
	}
	else{
		$stmt = $db->prepare("SELECT count(*) FROM pies
							WHERE nr_kkr is null and nr_ped is null and nr_kkw is not null and nr_kkw <> '';");
	}

	$stmt->execute();
	$lineageNr = $stmt->fetchColumn(); 
	
	
	$stmt = $db->prepare("SELECT grupa as breedGroup from rasa WHERE id_rasa = :breed;");
	$stmt->bindParam(':breed', $data->breed);
	$stmt->execute();
	
	$breedGroup = $stmt->fetch();

	$lineageId = GetLineageId($lineageNr + 1, $breedGroup['breedGroup']);
	$kkr = $isKkw == false ? $lineageId : null;
	$kkw = $isKkw == false ? null : $lineageId;
	$log -> addInfo("lineageId: " . $lineageId);	
	

	$stmt = $db->prepare("INSERT into pies (fullname, plec, data_ur, rasa, masc, data_rej, tytul, wyszkolenie, nr_ped,
						oznakowanie, hd, ed, dna, badania, testy_psych, nadane_upr, adnotacje, poprawnosc, badanie_dna,
						created, changed, creator, changed_by, nr_kkr, nr_kkw)
						VALUES (:nickname, :sex, :birthDate, :breed, :color, NOW(), :titles, :training, :nrPedigree,
						:marking, :hd, :ed, :dna, :otherChecks, :psychoTests, :breedingPermissions, :additionalInfo, 'nie', :dnaCheck,
						NOW(), NOW(), :userId, :userId, :kkr, :kkw);");
	
	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->bindParam(':sex', $data->sex);
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->bindParam(':color', $data->color);
	$stmt->bindParam(':titles', $data->titles);
	$stmt->bindParam(':training', $data->training);
	$stmt->bindParam(':nrPedigree', $data->nrPedigree);
    $stmt->bindParam(':marking', $data->marking);
	$stmt->bindParam(':hd', $data->hd);
	$stmt->bindParam(':ed', $data->ed);
	$stmt->bindParam(':dna', $data->dna);
	$stmt->bindParam(':otherChecks', $data->otherChecks);
	$stmt->bindParam(':psychoTests', $data->psychoTests);
	$stmt->bindParam(':breedingPermissions', $data->breedingPermissions);
	$stmt->bindParam(':additionalInfo', $data->additionalInfo);
	$stmt->bindParam(':dnaCheck', $data->dnaCheck);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':kkr', $kkr);
	$stmt->bindParam(':kkw', $kkw);
	
	$stmt->execute();
	$id = $db->lastInsertId();

	CreateDogBreederConnection($db, $data, $id);
	if ($data->owner != '') {
		CreateDogOwnerConnection($db, $data, $id);
	}

	return $id;
}

function UpdateDog($data, $db, $log, $userId, $id)
{
    if (!checkDogData($data)) {
		return 0;
	}
    $log -> addInfo("Updating dog: " .  $data->nickname);	
	
	$stmt = $db->prepare("UPDATE pies set fullname = :nickname, plec = :sex, rasa = :breed, masc = :color, 
						data_ur = :birthDate, tytul = :titles, wyszkolenie = :training, nr_ped = :nrPedigree,
						oznakowanie = :marking, hd = :hd, ed = :ed, dna = :dna, badania = :otherChecks,
						testy_psych = :psychoTests, nadane_upr = :breedingPermissions, adnotacje = :additionalInfo,
						badanie_dna = :dnaCheck, changed = NOW(), changed_by = :userId, poprawnosc = :accepted
						WHERE id_pies = :dogId;");

	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->bindParam(':sex', $data->sex);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->bindParam(':color', $data->color);
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':titles', $data->titles);
	$stmt->bindParam(':training', $data->training);
	$stmt->bindParam(':nrPedigree', $data->nrPedigree);
	$stmt->bindParam(':marking', $data->marking);
	$stmt->bindParam(':hd', $data->hd);
	$stmt->bindParam(':ed', $data->ed);
	$stmt->bindParam(':dna', $data->dna);
	$stmt->bindParam(':otherChecks', $data->otherChecks);
	$stmt->bindParam(':psychoTests', $data->psychoTests);
	$stmt->bindParam(':breedingPermissions', $data->breedingPermissions);
	$stmt->bindParam(':additionalInfo', $data->additionalInfo);
	$stmt->bindParam(':dnaCheck', $data->dnaCheck);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':accepted', $data->accepted);
	$stmt->bindParam(':dogId', $id);
	$stmt->execute();

	DeleteDogBreederConnectionByDogId($db, $id);
	CreateDogBreederConnection($db, $data, $id);
	if ($data->owner != '') {
		DeleteDogOwnerConnectionByDogId($db, $id);
		CreateDogOwnerConnection($db, $data, $id);
	}

    return $id;
}

function RemoveDog($id, $db, $log, $userId)
{
    $log -> addInfo("Removing dog: " . $id . " by " . $userId);
	DeleteDogBreederConnectionByDogId($db, $id);
	DeleteDogOwnerConnectionByDogId($db, $id);
	
	$stmt = $db->prepare("DELETE FROM pies WHERE id_pies = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	return true;
}

function GetDNAMembers($db, $dbw, $log)
{
    $log -> addInfo("Getting users with role DNA");
	$members = get_users( 'role=dna' );
	//print_r($members);
	$items = array();
	$i = 0;
	foreach($members as $member) {
		$items[$i]["Id"] = $member->ID;
		$items[$i]["Name"] = $member->data->display_name;
		$i++;
	}
	return json_encode($items);
}

function GetDogsAutoCompleteFromCommon($db, $filter, $sex)
{
	return GetDogsAutoComplete($db, $filter, $sex);
}

function GetBreedsAutoCompleteFromCommon($db, $filter)
{
	return GetBreedsAutoComplete($db, $filter);
}

function GetColorsAutoCompleteFromCommon($db, $filter)
{
	return GetColorsAutoComplete($db, $filter);
}

function GetPersonsAutoCompleteFromCommon($db, $filter)
{
	return GetPersonsAutoComplete($db, $filter);
}

function GetLineage($db, $id, $log, $nrOfGenerations)
{
	$log -> addInfo("Getting lineage for dog: " . $id);	
	$counter;
	switch ($nrOfGenerations) {
		case 2:
			$counter = 2;
			break;
		case 3:
			$counter = 4;
			break;
		case 4:
			$counter = 8;
			break;
		case 5:
			$counter = 16;
			break;
		case 6:
			$counter = 32;
			break;
		case 7:
			$counter = 64;
		break;
		case 8:
			$counter = 128;
			break;
		case 9:
			$counter = 256;
			break;
		default:
			# code...
			break;
	}
	$result = [];
	$i = 0;
	for ($j=0; $j < $counter; $j++) { 
		if ($j == 0) 
		{
			$result[$i] = GetDogInfoAndParents($db, $id);
			$i++;
		}
		else
		{
			$fatherId = $result[$j - 1]['fatherId'];
			if ($fatherId > 0) 
			{
				$result[$i] = GetDogInfoAndParents($db, $fatherId);
			}
			else
			{
				$result[$i] = '';
			}
			$i++;
			$motherId = $result[$j - 1]['motherId'];
			if ($motherId > 0) 
			{
				$result[$i] = GetDogInfoAndParents($db, $motherId);
			}
			else
			{
				$result[$i] = '';
			}
			$i++;
		}
	}

	return json_encode($result);
}

function GetDogInfoAndParents($db, $id)
{
	$stmt = $db->prepare("SELECT p.id_pies as dogId, p.fullname as nickname, f.id_pies as fatherId, m.id_pies as motherId, ma.masc as color,
						p.wyszkolenie as training, CONCAT('ED/', p.ed) as ed, CONCAT('HD/', p.hd) as hd, 
						case when p.dna = 'tak' then 'DNA' else '' end as dna, CONCAT('TP/', p.testy_psych) as psychoTests, 
						p.badania as otherChecks,
						p.data_ur as birthDate, case when p.nr_kkr is not null then CONCAT('KKR: ', p.nr_kkr) 
						else  p.nr_ped end as kkr, p.tytul as titles
						FROM pies p
						LEFT JOIN pies f on f.id_pies = p.id_sire
						LEFT JOIN pies m on m.id_pies = p.id_dam
						LEFT JOIN masc ma on ma.id_masc = p.masc
						WHERE p.id_pies = :id;");

	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$lineage = $stmt->fetch();
	return $lineage;
}

function SetParent($db, $log, $data, $userId)
{
	$log -> addInfo("Setting parent for dog: " . $data->dogId . " parentId: " . $data->parentId . ' is father: ' . $data->isFather);	

	if ($data->isFather === 1) {
		$stmt = $db->prepare("UPDATE pies set id_sire = :parentId, changed = NOW(), changed_by = :userId where id_pies = :dogId");
	}
	else{
		$stmt = $db->prepare("UPDATE pies set id_dam = :parentId, changed = NOW(), changed_by = :userId where id_pies = :dogId");
	}
	$stmt->bindParam(':parentId', $data->parentId);
	$stmt->bindParam(':dogId', $data->dogId);
	$stmt->bindParam(':userId', $userId);
	$stmt->execute();
	
	return true;
}

function DeleteParent($db, $log, $childId, $isFather, $userId)
{
	$log -> addInfo("Removing parent for dog: " . $childId . " isFather: " . $isFather);	
	
	if ($isFather == 1) {
		$stmt = $db->prepare("UPDATE pies set id_sire = null, changed = NOW(), changed_by = :userId where id_pies = :dogId");
	}
	else{
		$stmt = $db->prepare("UPDATE pies set id_dam = null, changed = NOW(), changed_by = :userId where id_pies = :dogId");
	}
	$stmt->bindParam(':dogId', $childId);
	$stmt->bindParam(':userId', $userId);
	$stmt->execute();
	
	return true;	
}

function GetDogsEntryBook($db, $log)
{
	$log -> addInfo("Getting dogs entry book.");
	$stmt = $db->prepare("SELECT p.id_pies as id, p.fullname as nickname, p.nr_kkw as lineage, p.poprawnosc as accepted, 
                        r.rasa as breed, p.plec as sex, CASE WHEN wp.id_pies is null THEN '' 
                        ELSE Concat(o.imie, ' ', o.nazwisko) END as owner
                        FROM pies p
                        JOIN rasa r on r.id_rasa = p.rasa
                        LEFT JOIN wlasciciel_pies wp on wp.id_pies = p.id_pies
                        LEFT JOIN osoba o on o.id_osoba = wp.id_osoba
                        WHERE p.nr_kkw is not null and p.nr_kkw <> '';");
	
	$stmt->execute();
    $dogsWithLineage = $stmt->fetchAll();
	
	return json_encode($dogsWithLineage);
}