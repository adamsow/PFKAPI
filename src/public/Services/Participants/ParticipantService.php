<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';

function GetExhibitionParticipants($db, $log, $id)
{
	$log -> addInfo("Getting participants for exhibition id: " .  $id);
	
	$stmt = $db->prepare("SELECT u.id, u.fullname as name, u.nr_rod as lineage, r.rasa as breed, u.klasa as class, CONCAT(u.imie, ' ', u.nazwisko) as ownerName
						FROM Uczestnicy u 
						JOIN rasa r on r.id_rasa = u.rasa
						WHERE u.wystawa_id = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	
	$participants = json_encode($stmt->fetchAll());
	
	return $participants;
}

function GetExhibitionParticipant($db, $log, $id, $dbw)
{
	$stmt = $db->prepare("SELECT u.id, u.fullname as nickname, u.plec as sex, u.data_ur as birthDate, r.id_rasa as breed, m.id_masc as color, u.klasa as class, 
						u.data_zg as applicationDate, u.tytul as title, u.wyszkolenie as training, u.nr_rod as lineage, u.oznakowanie as marking, u.sire as father, u.dam as mother,
						u.hod_imie as breederName, u.hod_nazwisko as breederSurname, u.imie as ownerName, u.nazwisko as ownerSurname, u.ulica as street, u.kod as postal, 
						u.miejscowosc as city, u.region as voivo, u.panstwo as country, u.tel as mobile, u.email, u.czlonek as member, u.testy_psych as tests, u.reproduktor1 as stud1,
						u.reproduktor2 as stud2, u.reproduktor3 as stud3, u.reproduktor4 as stud4, u.reproduktor5 as stud5, u.reproduktor6 as stud6, u.suka1 as bitch1, u.suka2 as bitch2, 
						u.suka3 as bitch3, u.suka4 as bitch4, u.suka5 as bitch5, u.suka6 as bitch6, u.para1 as pair1, u.para2 as pair2, u.hodowlana1 as kennel1, u.hodowlana2 as kennel2,
						u.hodowlana3 as kennel3, u.hodowlana4 as kennel4, u.hodowlana5 as kennel5, u.hodowlana6 as kennel6, u.adnotacje as additionalInfo, u.zatwierdzono as accepted,
						u.changed as modified, u.changed_by, u.created_by, u.ocena as mark, u.lokata as place, u.certyfikat as certificate, tytuly as exTitles
						FROM Uczestnicy u 
						JOIN rasa r on r.id_rasa = u.rasa
						JOIN masc m on m.id_masc = u.masc
						WHERE id = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$participant = $stmt->fetch();
	$createdBy = GetUser($participant['created_by'], $dbw);
	$modifiedBy = GetUser($participant['changed_by'], $dbw);
	$participant['created_by'] = $createdBy;
	$participant['changed_by'] = $modifiedBy;
	
	$participant = json_encode($participant);
	
	return $participant;
}

function AddParticipant($data, $db, $log, $userId)
{	
	$log -> addInfo("Adding new participant for exhibition: " . $data->exFullName);
	if(!checkExhibitionFormData($data))
		return "validation_error";
	
	if(applicationAlreadyExists($db, $data))
		return "already_exists";
	
	AddNewParticipant($data, $db, $userId);
		
	return true;
}

function AddNewParticipant($data, $db, $userId)
{
	$classString = GetClass($data->class);
	$stmt = $db->prepare("
		INSERT INTO Uczestnicy (wystawa_id, fullname, plec, data_ur, rasa, masc, klasa, data_zg, tytul, wyszkolenie, nr_rod, oznakowanie, sire, dam, hod_imie, 
		hod_nazwisko, imie, nazwisko, ulica, kod, miejscowosc, region, panstwo, tel, email, czlonek, testy_psych, reproduktor1, reproduktor2, reproduktor3, reproduktor4, 
		reproduktor5, reproduktor6, suka1, suka2, suka3, suka4, suka5, suka6, para1, para2, hodowlana1, hodowlana2, hodowlana3, hodowlana4, hodowlana5, hodowlana6, 
		adnotacje, zatwierdzono, changed, changed_by, created_by, ocena, lokata, certyfikat, tytuly) 
		VALUES (:id, :nickname, :sex, :birthDate, :breed, :color, :class, NOW(), :titles, :training, :lineage, :marking, :father, :mother, :breederName, :breederSurname, 
		:ownerName, :ownerSurname, :ownerStreet, :ownerPostal, :ownerCity, :ownerVoivodeship, :ownerCountry, :ownerMobile, :ownerEmail, :member, :psychoTest, :stud1, :stud2, 
		:stud3, :stud4, :stud5, :stud6, :bitch1, :bitch2, :bitch3, :bitch4, :bitch5, :bitch6, :pair1, :pair2, :kennel1, :kennel2, :kennel3, :kennel4, :kennel5, :kennel6, 
		:additionalInfo, :isAccepted, NOW(), :userId, :userId, :mark, :place, :certificate, :exTitles);
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
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':additionalInfo', $data->additionalInfo);
	$stmt->bindParam(':mark', $data->mark);
	$stmt->bindParam(':place', $data->place);
	$stmt->bindParam(':certificate', $data->certificate);
	$stmt->bindParam(':exTitles', $data->exTitles);
    $stmt->bindParam(':isAccepted', $data->isAccepted);
	
	$stmt->execute();
}

function UpdateParticipant($id, $data, $db, $log, $userId)
{
	if($id == '')
		return false;
	
	if(!checkExhibitionFormData($data))
		return false;
	
	$log -> addInfo("Updating participant: " . $id . " for exhibition: " . $data->exFullName);
	
	$classString = GetClass($data->class);
	$stmt = $db->prepare("
		Update Uczestnicy  set fullname = :nickname, plec = :sex, data_ur = :birthDate, rasa = :breed, masc = :color, klasa = :class, tytul = :titles, wyszkolenie = :training, 
		nr_rod = :lineage, oznakowanie = :marking, sire = :father, dam = :mother, hod_imie = :breederName, hod_nazwisko = :breederSurname, imie = :ownerName, nazwisko = :ownerSurname,
		ulica = :ownerStreet, kod = :ownerPostal, miejscowosc = :ownerCity, region = :ownerVoivodeship, panstwo = :ownerCountry, tel = :ownerMobile, email = :ownerEmail, czlonek = :member,
		testy_psych = :psychoTest, reproduktor1 = :stud1, reproduktor2 = :stud2, reproduktor3 = :stud3, reproduktor4 = :stud4, reproduktor5 = :stud5, reproduktor6 = :stud6, 
		suka1 = :bitch1, suka2 = :bitch2, suka3 = :bitch3, suka4 = :bitch4, suka5 = :bitch5, suka6 = :bitch6, para1 = :pair1, para2 = :pair2, hodowlana1 = :kennel1, hodowlana2 = :kennel2,
		hodowlana3 = :kennel3, hodowlana4 = :kennel4, hodowlana5 = :kennel5, hodowlana6 = :kennel6, adnotacje = :additionalInfo, zatwierdzono = :isAccepted, changed = NOW(), ocena = :mark,
		lokata = :place, certyfikat = :certificate, tytuly = :exTitles, changed_by = :userId
		WHERE id = :id;
		");
	
	$stmt->bindParam(':id', $id);
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
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':additionalInfo', $data->additionalInfo);
	$stmt->bindParam(':isAccepted', $data->isAccepted);
	$stmt->bindParam(':mark', $data->mark);
	$stmt->bindParam(':place', $data->place);
	$stmt->bindParam(':certificate', $data->certificate);
	$stmt->bindParam(':exTitles', $data->exTitles);
	
	$stmt->execute();
			
	return true;
}

function RemoveParticipant($id, $db, $log, $userId)
{
	$log -> addInfo("Removing participant: " . $id . " by " . $userId);
	$stmt = $db->prepare("Delete from Uczestnicy where id = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	return true;
}

function GetExhibitionParticipantsAll($db, $log, $id, $filter, $dbw)
{
	$log -> addInfo("Getting all participants for exhibition id: " .  $id . " for filter: " . $filter);
	switch($filter)
	{
		case "all":
			$condition = '';
			$order = 'r.grupa, r.rasa, u.klasa, u.plec, u.fullname';
			break;
		case "competition":
			$condition = "AND (u.reproduktor1 <> '' or u.suka1 <> '' or u.para1 <> '' or u.hodowlana1 <> '')";
			$order = 'r.rasa, u.fullname';
			break;
		case "tests":
			$condition = "AND u.testy_psych = 'tak'";
			$order = 'u.testy_psych, r.rasa, u.fullname';
			break;
		default:
			$condition = '';
			$order = 'u.fullname';
			break;
	}
	
	$stmt = $db->prepare("SELECT u.id, u.fullname as pełna_nazwa, u.plec, u.data_ur as data_urodzenia, r.rasa, r.breed, r.grupa, m.masc as maść, u.klasa, u.data_zg as data_zgłoszenia, u.tytul as tytuł,
						u.wyszkolenie, u.nr_rod as nr_rodowodu, u.oznakowanie, u.sire as ojciec, u.dam as matka, u.hod_imie as imię_hodowcy, u.hod_nazwisko as nazwisko_hodowcy,
						u.imie as imie_właściciela, u.nazwisko as nazwisko_właściciela, u.ulica, u.kod, u.miejscowosc as miejscowość, u.region, u.panstwo as państwo, u.tel, u.email, 
						u.czlonek as członek, u.testy_psych, u.reproduktor1, u.reproduktor2, u.reproduktor3, u.reproduktor4, u.reproduktor5, u.reproduktor6, u.suka1,  u.suka2, 
						u.suka3, u.suka4, u.suka5, u.suka6, u.para1, u.para2, u.hodowlana1, u.hodowlana2, u.hodowlana3, u.hodowlana4, u.hodowlana5, u.hodowlana6, u.adnotacje, u.zatwierdzono,
						u.changed as zmieniono, u.changed_by as przez
						FROM Uczestnicy u 
						JOIN rasa r on r.id_rasa = u.rasa
						JOIN masc m on m.id_masc = u.masc
						WHERE u.wystawa_id = :id " . $condition . "
						 ORDER BY " . $order . ";");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$participants = $stmt->fetchAll();
	
	$i = 0;
	foreach ($participants as $val) {
		$id = $val['przez'];
		if($id !== '')
			$user = GetUser($id, $dbw);
		
		$participants[$i]['przez'] = $user;
		$i++;
	}
	
	$participants = json_encode($participants);
	
	return $participants;
}