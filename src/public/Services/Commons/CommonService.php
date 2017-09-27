<?php
include_once("CommonServiceHelperFunctions.php");

function GetBreedings($db, $log)
{
	$log -> addInfo("Getting breedings.");
	$stmt = $db->prepare("SELECT grupa, o.imie, o.nazwisko, o.miejscowosc, o.region, o.email, o.tel_kom, o.tel_stac, h.przydomek, 
						h.pisany, h.data_rej, r.rasa, r.breed, h.www as website, h.www_widoczne as showWWW
						FROM osoba o 
						LEFT JOIN czlonek_hodowla ch ON o.czlonek = ch.nr_leg 
						LEFT JOIN czlonek cz ON o.czlonek = cz.nr_leg 
						LEFT JOIN logowanie l ON o.czlonek = l.nr_leg 
						LEFT JOIN hodowla h ON ch.nr_hod = h.nr_hod 
						LEFT JOIN rasa_hodowla rh ON h.nr_hod = rh.nr_hod 
						LEFT JOIN rasa r ON rh.id_rasa = r.id_rasa 
						WHERE czlonek IS NOT NULL 
						AND ch.nr_leg IS NOT NULL 
						AND l.status != 'blokada' 
						AND (cz.data_stop IS NULL OR cz.data_stop = '') 
						AND rasa IS NOT NULL 
						AND (h.data_wrej IS NULL OR h.data_wrej = '') 
						ORDER BY grupa, r.rasa, h.przydomek, o.nazwisko, o.imie;");
	$stmt->execute();
	$breedings = $stmt->fetchAll();
	
	return json_encode($breedings);
}

function GetStuds($db, $log)
{
	$log -> addInfo("Getting studs.");
	$stmt = $db->prepare("SELECT grupa, p.fullname, p.tytul, p.wyszkolenie, p.data_ur, r.rasa, r.breed, m.masc, m.colour, o.imie, o.nazwisko, o.email, o.tel_kom, o.tel_stac 
		FROM pies p 
		LEFT JOIN rasa r ON p.rasa = r.id_rasa 
		LEFT JOIN masc m ON p.masc = m.id_masc 
		LEFT JOIN wlasciciel_pies wp ON wp.id_pies = p.id_pies 
		LEFT JOIN osoba o ON o.id_osoba = wp.id_osoba 
		LEFT JOIN czlonek cz ON o.czlonek = cz.nr_leg 
		LEFT JOIN logowanie l ON o.czlonek = l.nr_leg 
		WHERE o.czlonek IS NOT NULL AND l.status != 'blokada' 
		AND (cz.data_stop IS NULL OR cz.data_stop = '') 
		AND r.rasa IS NOT NULL 
		AND (p.data_sm IS NULL OR p.data_sm = '') 
		AND plec = 'pies' 
		AND (nadane_upr = 'pelne' OR nadane_upr = 'rok') 
		ORDER BY grupa, r.rasa, p.fullname, o.nazwisko, o.imie;");
	$stmt->execute();
	$studs = $stmt->fetchAll();
	
	return json_encode($studs);
}

function GetLitters($db, $log)
{
	$log -> addInfo("Getting litters.");
	$stmt = $db->prepare("SELECT grupa, h.przydomek as hodowla, m.data_ur, r.rasa, r.breed 
		FROM osoba o 
		LEFT JOIN czlonek_hodowla ch ON o.czlonek = ch.nr_leg 
		LEFT JOIN czlonek cz ON o.czlonek = cz.nr_leg 
		LEFT JOIN logowanie l ON o.czlonek = l.nr_leg 
		LEFT JOIN hodowla h ON ch.nr_hod = h.nr_hod 
		LEFT JOIN miot m ON m.przydomek = h.nr_hod 
		LEFT JOIN rasa r ON m.rasa = r.id_rasa 
		WHERE ch.nr_leg IS NOT NULL AND l.status != 'blokada' 
		AND (cz.data_stop IS NULL OR cz.data_stop = '') 
		AND r.rasa IS NOT NULL 
		AND (h.data_wrej IS NULL OR h.data_wrej = '') 
		AND m.data_ur > CURDATE() - INTERVAL 7 MONTH 
		GROUP BY m.nr_miot 
		ORDER BY grupa, r.rasa, m.data_ur desc, h.przydomek;");
	$stmt->execute();
	$litters = $stmt->fetchAll();
	
	return json_encode($litters);
}

function GetExhibitionData($db, $log)
{
	$log -> addInfo("Getting exhibition data for form.");
	//get exhibitions
	$stmt = $db->prepare("SELECT id_wystawa as id, pelna_nazwa as name from wystawa where zgloszenia_otwarte = true order by data;");
	$stmt->execute();
	$exhibitions = json_encode($stmt->fetchAll());
	//get breeds
	$breeds = json_encode(GetBreeds($db));
	//get color
	$colors = json_encode(GetColors($db));
	//get countries
	$countries = json_encode(GetCountries($db));
	
	$result = '{"exhibition":' . $exhibitions . ',"breed":' . $breeds . ',"color":' . $colors . ',"country":' . $countries . '}';

	return $result;
}

function SaveExhibitionData($db, $log, $data)
{
	$log -> addInfo("Saving exhibition data.");
	if(!checkExhibitionFormData($data))
		return "validation_error";
	
	if(applicationAlreadyExists($db, $data))
		return "already_exists";

	AddNewParticipant($data, $db);
	
	$message = PrepareExhibitionMessage($data);
	$fullname = $data->ownerName . " " . $data->ownerSurname;
	SendEmail($data->ownerEmail, $fullname, $message, "Zgloszenie na wystawe", "wystawy@pfk.org.pl", "Wystawy PFK", "!?abcTUPO657?", true);

	return "OK";
}

function AddNewParticipant($data, $db)
{
	$classString = GetClass($data->class);
	$stmt = $db->prepare("
		INSERT INTO Uczestnicy (wystawa_id, fullname, plec, data_ur, rasa, masc, klasa, data_zg, tytul, wyszkolenie, nr_rod, oznakowanie, sire, dam, hod_imie, 
		hod_nazwisko, imie, nazwisko, ulica, kod, miejscowosc, region, panstwo, tel, email, czlonek, testy_psych, reproduktor1, reproduktor2, reproduktor3, reproduktor4, 
		reproduktor5, reproduktor6, suka1, suka2, suka3, suka4, suka5, suka6, para1, para2, hodowlana1, hodowlana2, hodowlana3, hodowlana4, hodowlana5, hodowlana6, 
		 zatwierdzono, changed) 
		VALUES (:id, :nickname, :sex, :birthDate, :breed, :color, :class, NOW(), :titles, :training, :lineage, :marking, :father, :mother, :breederName, :breederSurname, 
		:ownerName, :ownerSurname, :ownerStreet, :ownerPostal, :ownerCity, :ownerVoivodeship, :ownerCountry, :ownerMobile, :ownerEmail, :member, :psychoTest, :stud1, :stud2, 
		:stud3, :stud4, :stud5, :stud6, :bitch1, :bitch2, :bitch3, :bitch4, :bitch5, :bitch6, :pair1, :pair2, :kennel1, :kennel2, :kennel3, :kennel4, :kennel5, :kennel6, 
		 'nie', NOW());
		");
	
	$stmt->bindParam(':id', $data->exhibition);
	$stmt->bindParam(':nickname', $data->nickname);
	$stmt->bindParam(':sex', $data->sex);
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->bindParam(':color', $data->color);
	$stmt->bindParam(':class', $classString);
	$stmt->bindParam(':titles', $data->titles);
	$stmt->bindParam(':training', $data->training);
	$stmt->bindParam(':lineage', $data->lineage);
	$stmt->bindParam(':marking', $data->marking);
	$stmt->bindParam(':father', $data->father);
	$stmt->bindParam(':mother', $data->mother);
	$stmt->bindParam(':breederName', $data->breederName);
	$stmt->bindParam(':breederSurname', $data->breederSurname);
	$stmt->bindParam(':ownerName', $data->ownerName);
	$stmt->bindParam(':ownerSurname', $data->ownerSurname);
	$stmt->bindParam(':ownerStreet', $data->ownerStreet);
	$stmt->bindParam(':ownerPostal', $data->ownerPostal);
	$stmt->bindParam(':ownerCity', $data->ownerCity);
	$stmt->bindParam(':ownerVoivodeship', $data->ownerVoivodeship);
	$stmt->bindParam(':ownerCountry', $data->ownerCountry);
	$stmt->bindParam(':ownerMobile', $data->ownerMobile);
	$stmt->bindParam(':ownerEmail', $data->ownerEmail);
	$stmt->bindParam(':member', $data->isMember);
	$stmt->bindParam(':psychoTest', $data->psychoTest);
	$stmt->bindParam(':stud1', $data->stud1);
	$stmt->bindParam(':stud2', $data->stud2);
	$stmt->bindParam(':stud3', $data->stud3);
	$stmt->bindParam(':stud4', $data->stud4);
	$stmt->bindParam(':stud5', $data->stud5);
	$stmt->bindParam(':stud6', $data->stud6);
	$stmt->bindParam(':bitch1', $data->bitch1);
	$stmt->bindParam(':bitch2', $data->bitch2);
	$stmt->bindParam(':bitch3', $data->bitch3);
	$stmt->bindParam(':bitch4', $data->bitch4);
	$stmt->bindParam(':bitch5', $data->bitch5);
	$stmt->bindParam(':bitch6', $data->bitch6);
	$stmt->bindParam(':pair1', $data->pair1);
	$stmt->bindParam(':pair2', $data->pair2);
	$stmt->bindParam(':kennel1', $data->kennel1);
	$stmt->bindParam(':kennel2', $data->kennel2);
	$stmt->bindParam(':kennel3', $data->kennel3);
	$stmt->bindParam(':kennel4', $data->kennel4);
	$stmt->bindParam(':kennel5', $data->kennel5);
	$stmt->bindParam(':kennel6', $data->kennel6);
	
	$stmt->execute();
}

function GetMembersForAutoComplete($db, $filter)
{
	$filter = '%' . $filter . '%';
	$stmt = $db->prepare("SELECT cz.nr_leg as ownerId, CONCAT(o.imie, ' ', o.nazwisko) as ownerName, cz.przynaleznosc as department
						FROM czlonek cz
						JOIN osoba o on o.czlonek = cz.nr_leg
						WHERE cz.nr_leg LIKE :filter OR o.imie LIKE :filter OR o.nazwisko LIKE :filter 
						OR CONCAT(o.imie, ' ', o.nazwisko) LIKE :filter;");
	
	$stmt->bindParam(':filter', $filter);
	
	$stmt->execute();
	$members = $stmt->fetchAll();

	return json_encode($members);
}

function GetDogsAutoCompleteForPublic($db, $filter)
{
	$filter = '%' . $filter . '%';
	$stmt = $db->prepare("SELECT  p.fullname as nickname, r.rasa as breedName, p.data_ur as birthDate
						FROM pies p
            			LEFT JOIN rasa r on r.id_rasa = p.rasa
						WHERE fullname LIKE :filter;");
	
	$stmt->bindParam(':filter', $filter);
	
	$stmt->execute();
	$dogs = $stmt->fetchAll();
	return json_encode($dogs);
}