<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';
require_once __DIR__ . '/../Members/MemberService.php';
require_once('ValidateSingleMemberService.php');

function GetMyDetails($db, $memberId, $log)
{
	$log -> addInfo("Getting my details for member: " . $memberId);
	
	$stmt = $db->prepare("SELECT cz.nr_leg as memberId, o.imie as name, o.nazwisko as surname, o.ulica as street, o.miejscowosc as city, o.kod as postal, 
						o.region as voivodeship, o.email, COALESCE(o.tel_kom, o.tel_stac) as phone, cz.skladka as fee, 
						cz.przynaleznosc as department, cz.data_przys as startDate
						FROM czlonek cz
						JOIN osoba o on o.czlonek = cz.nr_leg
						WHERE cz.nr_leg = :id;");
	
	$stmt->bindParam(':id', $memberId);
	$stmt->execute();
	$myDetails = json_encode($stmt->fetch());
	
	return $myDetails;
}

function UpdateMyDetails($data, $db, $log, $userId, $memberId, $changeEmail, $oldEmail)
{
    $log -> addInfo("Updating my details for member: " . $memberId);

    if (!checkMyDetails($data)) {
		return 0;
    }

    if ($changeEmail) {
		UdpateEmail($data->email, $oldEmail);
    }
    
    $stmt = $db->prepare("UPDATE osoba set tel_kom = :phone, email = :email, changed_by = :userId,
						changed = NOW()
						WHERE czlonek = :memberId;");

    $stmt->bindParam(':phone', $data->phone);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':userId', $userId);
    $stmt->bindParam(':memberId', $memberId);

    $stmt->execute();

    return $memberId;
}

function GetMyBreedings($db, $memberId, $log)
{
	$log -> addInfo("Getting my breedings for member: " . $memberId);
	
	$stmt = $db->prepare("SELECT h.przydomek as nickname, h.nr_hod as breedingId, h.data_rej as registrationDate, o.imie as name, o.nazwisko as surname,
						h.www as website, h.www_widoczne as isWebSiteVisible
						FROM hodowla h
						JOIN czlonek_hodowla czh on czh.nr_hod = h.nr_hod
            			JOIN osoba o on o.czlonek = czh.nr_leg
						WHERE czh.nr_leg = :id;");
	
	$stmt->bindParam(':id', $memberId);
	$stmt->execute();
	$myBreedings = $stmt->fetch();

	//get breeds
	$stmt = $db->prepare("SELECT r.rasa as breed, r.id_rasa as breedId 
	FROM rasa_hodowla rh 
	JOIN rasa r on rh.id_rasa = r.id_rasa 
	WHERE rh.nr_hod = :id;");

	$stmt->bindParam(':id', $myBreedings['breedingId']);
	$stmt->execute();

	$breeds = $stmt->fetchAll();
	$myBreedings['breeds'] = $breeds;
	
	return json_encode($myBreedings);
}

function UpdateMyBreedings($data, $db, $log, $userId, $memberId)
{
	$log -> addInfo("Updating my breedings for member: " . $memberId);
	
	if (!checkMyBreedings($data)) {
		return 0;
	}

	$stmt = $db->prepare("UPDATE hodowla set www = :website, changed_by = :userId, changed = NOW()
						WHERE nr_hod = :breedingId
						AND (select nr_leg from czlonek_hodowla where nr_hod = :breedingId) = :memberId;"); //secure that member will update only his breeding

	$stmt->bindParam(':website', $data->website);
	$stmt->bindParam(':breedingId', $data->breedingId);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':memberId', $memberId);

	$stmt->execute();

	DeleteBreedingBreedConnection($db, $data->breedingId);
	CreateBreedingBreedConnection($db, $data->breedingId, $data);

	return $memberId;
}

function GetBreedsAutoCompleteFromCommon($db, $filter)
{
	return GetBreedsAutoComplete($db, $filter);
}

function GetMyDogs($db, $memberId, $log)
{
	$log -> addInfo("Getting my dogs for member: " . $memberId);
	
	$stmt = $db->prepare("SELECT p.id_pies as dogId, p.fullname, p.plec as sex, r.rasa as breed, p.nr_kkr as kkr
						FROM pies p
						JOIN wlasciciel_pies wp on wp.id_pies = p.id_pies
						JOIN osoba o on o.id_osoba = wp.id_osoba
						JOIN rasa r on r.id_rasa = p.rasa
						Where o.czlonek = :id;");
	
	$stmt->bindParam(':id', $memberId);
	$stmt->execute();
	$myDogs = $stmt->fetchAll();

	return json_encode($myDogs);
}

function DeleteMyDog($db, $memberId, $log, $id)
{
	$log -> addInfo("Deleting my dog for member: " . $memberId . " dog id: " . $id);
	
	$stmt = $db->prepare("DELETE from wlasciciel_pies 
						WHERE id_osoba = (SELECT id_osoba from osoba where czlonek = :memberId) and id_pies = :id;");
	
	$stmt->bindParam(':memberId', $memberId);
	$stmt->bindParam(':id', $id);
	$stmt->execute();
}