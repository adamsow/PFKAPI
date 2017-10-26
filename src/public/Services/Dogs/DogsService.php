<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';
require_once('ValidateDogsService.php');

function GetDogsWithLineages($db, $log)
{
	$log -> addInfo("Getting dogs with lineage.");
	$stmt = $db->prepare("SELECT p.id_pies as id, p.fullname as nickname, p.nr_kkr as lineage, 
                        r.rasa as breed, p.plec as sex, Concat(o.imie, ' ', o.nazwisko) as owner
                        FROM pies p
                        JOIN rasa r on r.id_rasa = p.rasa
                        LEFT JOIN wlasciciel_pies wp on wp.id_pies = p.id_pies
                        LEFT JOIN osoba o on o.id_osoba = wp.id_osoba
                        WHERE p.nr_kkr is not null and p.nr_kkr <> '';");
	
	$stmt->execute();
    $dogsWithLineage = $stmt->fetchAll();
	
	return json_encode($dogsWithLineage);
}

function GetDNAById($db, $log, $dbw, $id)
{
    $log -> addInfo("Getting info about DNA data: " . $id);	
	$stmt = $db->prepare("SELECT d.fullname, d.owner, r.id_rasa as breedId, r.rasa as breed, m.id_masc as colorId, m.masc as color,
                        d.sex, d.birth_date as birthDate, d.lineage, d.marking, d.breeder,
                        d.owner_street, d.owner_city, d.owner_postal, d.owner_mobile, d.sampling_date as samplingDate, d.DNA_member_id as prober,
                        d.creator as created_by, d.created, d.changed as modified, d.changed_by, d.dog_id as dogId, d.ownerId, d.breederId, 
						COALESCE(cz.nr_leg, 0) as isOwnerMember, COALESCE(czl.nr_leg, 0) as isBreederMember, cz.przynaleznosc as ownerDepartment, 
						czl.przynaleznosc as breederDepartment
                        FROM DNA d
                        LEFT JOIN rasa r on r.id_rasa = d.breed_id
                        LEFT JOIN masc m on m.id_masc = d.color_id
                        LEFT JOIN czlonek cz on cz.nr_leg = d.ownerId
                        LEFT JOIN czlonek czl on czl.nr_leg = d.breederId
                        WHERE id = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$DNA = $stmt->fetch();

	$createdBy = GetUser($DNA['created_by'], $dbw);
    $modifiedBy = GetUser($DNA['changed_by'], $dbw);
	$DNA['created_by'] = $createdBy;
    $DNA['changed_by'] = $modifiedBy;
	
	$DNA = json_encode($DNA);
	
	return $DNA;
}

function DNAExists($db, $data)
{
    $stmt = $db->prepare("SELECT count(*)
						FROM DNA 
						WHERE birth_date = :birthDate and fullname = :fullname and lineage = :lineage and owner = :owner and breed_id = :breedId;");
	
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':fullname', $data->fullname);
	$stmt->bindParam(':lineage', $data->lineage);
    $stmt->bindParam(':owner', $data->owner);
	$stmt->bindParam(':breedId', $data->breedId);
    
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function DNAExistsWithAnotherId($db, $data, $id)
{
    $stmt = $db->prepare("SELECT count(*)
						FROM DNA 
						WHERE birth_date = :birthDate and fullname = :fullname and lineage = :lineage and owner = :owner 
						and breed_id = :breedId and id <> :id;");
	
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':fullname', $data->fullname);
	$stmt->bindParam(':lineage', $data->lineage);
    $stmt->bindParam(':owner', $data->owner);
	$stmt->bindParam(':breedId', $data->breedId);
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function AddDNA($data, $db, $log, $userId)
{
	if(!checkDNAData($data))
		return 0;

	$log -> addInfo("Adding DNA data: " . $data->samplingDate);	

	$stmt = $db->prepare("INSERT INTO DNA (fullname, sex, breed_id, color_id, birth_date, lineage, marking, owner,
                        owner_street, owner_city, owner_postal, owner_mobile, breeder, sampling_date, DNA_member_id,
						creator, created, changed_by, changed, dog_id, ownerId, breederId, isOwnerMember, isBreederMember) 
						VALUES (:fullname, :sex, :breedId, :colorId, :birthDate, :lineage, :marking, :owner, :ownerStreet,
						 :ownerCity, :ownerPostal, :ownerMobile, :breeder, :samplingDate, :prober, :userId, NOW(), :userId, 
						 NOW(), :dogId, :ownerId, :breederId, :isOwnerMember, :isBreederMember);");
	$log -> addInfo("Adding DNA data color: " . $data->colorId);	
	
	$stmt->bindParam(':fullname', $data->fullname);
	$stmt->bindParam(':sex', $data->sex);
	$stmt->bindParam(':breedId', $data->breedId);
	$stmt->bindParam(':colorId', $data->colorId);
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':lineage', $data->lineage);
	$stmt->bindParam(':marking', $data->marking);
	$stmt->bindParam(':owner', $data->owner);
    $stmt->bindParam(':ownerStreet', $data->ownerStreet);
	$stmt->bindParam(':ownerCity', $data->ownerCity);
	$stmt->bindParam(':ownerPostal', $data->ownerPostal);
	$stmt->bindParam(':ownerMobile', $data->ownerMobile);
	$stmt->bindParam(':breeder', $data->breeder);
	$stmt->bindParam(':samplingDate', $data->samplingDate);
	$stmt->bindParam(':prober', $data->prober);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':dogId', $data->dogId);
	$stmt->bindParam(':ownerId', $data->ownerId);
	$stmt->bindParam(':breederId', $data->breederId);
	$stmt->bindParam(':isOwnerMember', $data->isOwnerMember);
	$stmt->bindParam(':isBreederMember', $data->isBreederMember);

	$stmt->execute();

	$id = $db->lastInsertId();

	return $id;
}
function UpdateDNA($data, $db, $log, $userId, $id)
{
    if (!checkDNAData($data)) {
		return 0;
	}
    $log -> addInfo("Updating DNA data: " .  $id);	
	$log -> addInfo("Adding DNA data color: " . $data->colorId);	
	
	$stmt = $db->prepare("UPDATE DNA set fullname = :fullname, sex = :sex, breed_id = :breedId, color_id = :colorId, 
						birth_date = :birthDate, lineage = :lineage, marking = :marking, owner = :owner, 
						owner_street = :ownerStreet, owner_city = :ownerCity, owner_postal = :ownerPostal,
						owner_mobile = :ownerMobile, breeder = :breeder, sampling_date = :samplingDate, 
						DNA_member_id = :prober, changed_by = :userId, changed = NOW(), dog_id = :dogId, ownerId = :ownerId,
						breederId = :breederId, isOwnerMember = :isOwnerMember, isBreederMember = :isBreederMember
						WHERE id = :id;");

	$stmt->bindParam(':fullname', $data->fullname);
	$stmt->bindParam(':sex', $data->sex);
	$stmt->bindParam(':breedId', $data->breedId);
	$stmt->bindParam(':colorId', $data->colorId);
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':lineage', $data->lineage);
	$stmt->bindParam(':marking', $data->marking);
	$stmt->bindParam(':owner', $data->owner);
	$stmt->bindParam(':ownerStreet', $data->ownerStreet);
	$stmt->bindParam(':ownerCity', $data->ownerCity);
	$stmt->bindParam(':ownerPostal', $data->ownerPostal);
	$stmt->bindParam(':ownerMobile', $data->ownerMobile);
	$stmt->bindParam(':breeder', $data->breeder);
	$stmt->bindParam(':samplingDate', $data->samplingDate);
	$stmt->bindParam(':prober', $data->prober);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':dogId', $data->dogId);
	$stmt->bindParam(':ownerId', $data->ownerId);
	$stmt->bindParam(':breederId', $data->breederId);
	$stmt->bindParam(':isOwnerMember', $data->isOwnerMember);
	$stmt->bindParam(':isBreederMember', $data->isBreederMember);
	$stmt->bindParam(':id', $id);

	$stmt->execute();

    return $id;
}

function RemoveDNA($id, $db, $log, $userId)
{
    $log -> addInfo("Removing DNA data: " . $id . " by " . $userId);

	$stmt = $db->prepare("DELETE FROM DNA WHERE id = :id;");
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

function GetDogsAutoCompleteFromCommon($db, $filter)
{
	return GetDogsAutoComplete($db, $filter);
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