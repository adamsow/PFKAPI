<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';
require_once('ValidatePersonService.php');

function GetPersons($db, $log)
{
	$log -> addInfo("Getting persons.");

	
	$stmt = $db->prepare("SELECT o.id_osoba as Id, o.imie as name, o.nazwisko as surname, p.kraj as country, o.miejscowosc as city 
                        FROM osoba o 
                        JOIN panstwo p ON o.panstwo = p.id_panstwo 
                        WHERE o.czlonek IS NULL");

	$stmt->bindParam(':department', $department);
	$stmt->execute();
	$persons = json_encode($stmt->fetchAll());
	
	return $persons;
}

function GetPersonById($db, $log, $id, $dbw)
{
    $log -> addInfo("Getting info about member: " . $id);	
	$stmt = $db->prepare("SELECT imie as name, nazwisko as surname, ulica as street, kod as postal, miejscowosc as city, 
                        region as voivodeship, panstwo as country, tel_stac as phone, tel_kom as mobile, email, creator as created_by,
                        created, changed as modified, changed_by
                        FROM osoba 
                        WHERE id_osoba = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$person = $stmt->fetch();

	$createdBy = GetUser($person['created_by'], $dbw);
	$modifiedBy = GetUser($person['changed_by'], $dbw);
	$person['created_by'] = $createdBy;
	$person['changed_by'] = $modifiedBy;
	
	//Get own dogs
	$stmt = $db->prepare("SELECT p.id_pies as dogId, p.fullname as dogName
						FROM pies p
						JOIN wlasciciel_pies wp on wp.id_pies = p.id_pies
						JOIN osoba o on o.id_osoba = wp.id_osoba
						where o.id_osoba = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$ownDogs = $stmt->fetchAll();
	$person['ownDogs'] = $ownDogs;	

	//Get breeder dogs
	$stmt = $db->prepare("SELECT p.id_pies as dogId, p.fullname as dogName
						FROM pies p
						JOIN hodowca_pies wp on wp.id_pies = p.id_pies
						JOIN osoba o on o.id_osoba = wp.id_osoba
						where o.id_osoba = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$breederDogs = $stmt->fetchAll();
	$person['breederDogs'] = $breederDogs;	
	
	
	$person = json_encode($person);
	
	return $person;
}

function AddPerson($data, $db, $log, $userId, $dbw)
{
	if(!checkPersonData($data))
		return 0;

	$log -> addInfo("Adding person: " . $data->name . " " . $data->surname);	

	$stmt = $db->prepare("INSERT INTO osoba (imie, nazwisko, ulica, kod, miejscowosc, region, panstwo, tel_stac, tel_kom, email, 
					      creator, created, changed_by, changed) 
					      VALUES (:name, :surname, :street, :postal, :city, :voivo, :country, :phone, :mobile, :email, 
						  :userId, NOW(), :userId, NOW());
						  ");

	$stmt->bindParam(':name', $data->name);
	$stmt->bindParam(':surname', $data->surname);
	$stmt->bindParam(':street', $data->street);
	$stmt->bindParam(':postal', $data->postal);
	$stmt->bindParam(':city', $data->city);
	$stmt->bindParam(':voivo', $data->voivodeship);
	$stmt->bindParam(':country', $data->country);
	$stmt->bindParam(':phone', $data->phone);
	$stmt->bindParam(':mobile', $data->mobile);
	$stmt->bindParam(':email', $data->email);
	$stmt->bindParam(':userId', $userId);

	$stmt->execute();

	$id = $db->lastInsertId();

	return $id;
}

function UpdatePerson($data, $db, $log, $userId, $id)
{
    if (!checkPersonData($data)) {
		return 0;
	}

	$stmt = $db->prepare("UPDATE osoba set imie = :name, nazwisko = :surname, ulica = :street, kod = :postal, miejscowosc = :city, 
						region = :voivo, panstwo = :country, tel_stac = :phone, tel_kom = :mobile, email = :email, changed_by = :userId,
						changed = NOW()
						WHERE id_osoba = :id;");

	$stmt->bindParam(':name', $data->name);
	$stmt->bindParam(':surname', $data->surname);
	$stmt->bindParam(':street', $data->street);
	$stmt->bindParam(':postal', $data->postal);
	$stmt->bindParam(':city', $data->city);
	$stmt->bindParam(':voivo', $data->voivodeship);
	$stmt->bindParam(':country', $data->country);
	$stmt->bindParam(':phone', $data->phone);
	$stmt->bindParam(':mobile', $data->mobile);
	$stmt->bindParam(':email', $data->email);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':id', $id);

	$stmt->execute();

    return $id;
}

function RemovePerson($id, $db, $log, $userId)
{
    $log -> addInfo("Removing person: " . $id . " by " . $userId);

	$stmt = $db->prepare("DELETE FROM osoba WHERE id_osoba = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM pies 
						WHERE id_pies in (SELECT id_pies FROM hodowca_pies 
							WHERE id_osoba = :id);");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM hodowca_pies WHERE id_osoba = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM pies 
						WHERE id_pies in (select id_pies from wlasciciel_pies 
							where id_osoba = :id);");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM wlasciciel_pies WHERE id_osoba = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	return true;
}

function PersonExists($db, $data)
{
    return CheckIfPersonExists($db, $data);
}

function GetPersonConsts($db, $log)
{
    $countries = json_encode(GetCountries($db));
    return $countries;
}