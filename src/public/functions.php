<?php
use \Firebase\JWT\JWT;
include_once('helperFunctions.php');

function GetBreedings($db, $log)
{
	$log -> addInfo("Getting breedings.");
	$stmt = $db->prepare("SELECT grupa, o.imie, o.nazwisko, o.miejscowosc, o.region, o.email, o.tel_kom, o.tel_stac, h.przydomek, h.pisany, h.data_rej, r.rasa, r.breed 
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

	AddNewParticipant($data, $db, true, NULL);
	
	$message = PrepareExhibiotnMessage($data);
	$fullname = $data->ownerName . " " . $data->ownerSurname;
	SendEmail($data->ownerEmail, $fullname, $message, "Zgloszenie na wystawe", "wystawy@pfk.org.pl", "Wystawy PFK", "!?abcTUPO657?", true);

	return "OK";
}

function GetToken($username, $password, $dbw, $log, $secret)
{
	require_once('wp-functions.php');
	require_once('../vendor/firebase/php-jwt/src/JWT.php');
	require_once('../vendor/tuupola/slim-jwt-auth/src/JwtAuthentication.php');

	$log -> addInfo("Getting token for user: " . $username);
	$stmt = $dbw->prepare("SELECT wpu.user_pass, wpu.id, wpum.meta_value 
		FROM wp_users wpu join wp_usermeta wpum on wpum.user_id = wpu.id 
		WHERE (wpu.user_nicename=:username OR wpu.user_email=:username) AND meta_key ='wp_capabilities'");
	$stmt->bindParam(':username', $username);
	$stmt->execute();
	
	$user = $stmt->fetch();
	if($user['user_pass'] === NULL)
		return false;

	if(wp_check_password($password, $user['user_pass']) === false)
		return false;
	
	$roles = unserialize(stripslashes($user['meta_value']));
	$now = new DateTime();
	$future = new DateTime("now +24 hours");
	$rand = substr(md5(microtime()),rand(0,26),5);
	$jti = base64_encode($rand);
	$payload = [
		"username" => $username,
		"user_id" => $user['id'],
		"jti" => $jti,
		"iat" => $now->getTimeStamp(),
		"exp" => $future->getTimeStamp(),
		"iss" => "https://c-pfk.pl",
		"aud" => "https://pfk.org.pl",
		"scope" => array_keys($roles)
	];
		
	$token = JWT::encode($payload, $secret, "HS256");
	$log -> addInfo("Token for user: " . $username . " created succesfuly. Generated token: " . $token);
	
	return $token;
}

function GetExhibitions($db, $log, $filter){
	$log -> addInfo("Getting exhibitions.");
	switch($filter)
	{
		case "all":
			$condition = 'ORDER by data desc';
			break;
		case "active":
			$condition = 'where data >= CURDATE()';
			break;
		case "not-active":
			$condition = 'where data < CURDATE() ORDER by data desc';
			break;
		default:
			$condition = '';
			break;
	}
	
	$stmt = $db->prepare("SELECT id_wystawa as id, pelna_nazwa as name, data as date, data >= CURDATE() as isActive FROM wystawa " . $condition . ";");

	$stmt->execute();
	$exhibitions = json_encode($stmt->fetchAll());
	
	return $exhibitions;
}

function GetExhibitionById($db, $log, $id, $dbw)
{
	$log -> addInfo("Getting exhibition by ID.");	
	$stmt = $db->prepare("SELECT w.rodzaj as category, w.oddzial as department, w.miejscowosc as city, w.creator, w.changed_by, w.created, w.changed as modified, w.zgloszenia_otwarte as status
		FROM wystawa w
		WHERE id_wystawa = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$exhibition = $stmt->fetchAll();
	$createdBy = GetUser($exhibition[0]['creator'], $dbw);
	$modifiedBy = GetUser($exhibition[0]['changed_by'], $dbw);
	$exhibition['createdBy'] = $createdBy;
	$exhibition['modifiedBy'] = $modifiedBy;
	
	$exhibition = json_encode($exhibition);
	
	return $exhibition;
}

function GetDepartments($db, $log)
{
	$log -> addInfo("Getting departments.");
	$stmt = $db->prepare("SELECT oddzial as department from region;");
	$stmt->execute();
	$departments = json_encode($stmt->fetchAll());
	
	return $departments;
}

function CheckReferer($referer)
{
	return strrpos($referer, "https://pfk.org.pl");
}

function GetRoles($db, $log)
{
	$log -> addInfo("Getting roles.");
	$stmt = $db->prepare("SELECT option_value FROM wp_options WHERE option_name = 'wp_user_roles';");
	$stmt->execute();
	$roles = $stmt->fetch();
	
	$roles = unserialize(stripslashes($roles['option_value']));
	$roleKeys = array_keys($roles);

	$i = 0;
	foreach ($roles as $role) 
	{
		$roleKey = $roleKeys[$i];
		$roleNames[$roleKey] = $role['name'];
		$i++;
	}
	return json_encode($roleNames);
}

function GetApiSites($db, $log)
{
	$log -> addInfo("Getting api sites.");
	$stmt = $db->prepare("SELECT * FROM APISites;");
	$stmt->execute();
	$sites = $stmt->fetchAll();
	
	return json_encode($sites);
}

function GetRoleAssignments($db, $log)
{
	$log -> addInfo("Getting role assigments.");
	$stmt = $db->prepare("SELECT * FROM AccessRights;");
	$stmt->execute();
	$assigments = $stmt->fetchAll();
	
	return json_encode($assigments);
}

function SaveRoles($data, $db, $log)
{
	$log -> addInfo("Saving roles");
	$stmt = $db->prepare("DELETE FROM AccessRights;");
	$stmt->execute();
	foreach ($data as $role) 
	{
		$stmt = $db->prepare("INSERT INTO AccessRights (APISiteID, AccessLevelID, RoleName)
							VALUES (:apiSiteId, :accessLevelId, :roleName);");
		$stmt->bindParam(':apiSiteId', $role->ApiSiteID);
		$stmt->bindParam(':accessLevelId', $role->AccessLevel);
		$stmt->bindParam(':roleName', $role->Role);
		$stmt->execute();
	}
	
	return true;
}

function GetAccess($scope, $pageName, $db, $log)
{
	$in = join("','",$scope); 
	$log -> addInfo("Checking access for page: " . $pageName . ")");
	$stmt = $db->prepare("SELECT MAX(al.ID) as ID, al.Name 
						FROM AccessRights ar 
						JOIN AccessLevel al on al.ID = ar.AccessLevelID 
						WHERE APISiteID = (SELECT ID FROM APISites WHERE PageName = :pageName) 
						AND RoleName IN ('" . $in . "');");
	$stmt->bindParam(':pageName', $pageName);
	$stmt->execute();
	
	$access = $stmt->fetch();
	return $access['Name'];	
}

function HasWriteAccess($access)
{
	if($access === "ZAPIS" || $access === "ALL")
		return true;
	
	return false;
}

function HasAllAccess($access)
{
	if($access === "ALL")
		return true;
	
	return false;
}

function AddExhibition($data, $db, $log, $userId)
{
	if(!CheckNewExhibitionData($data))
		return false;
	
	$log -> addInfo("Adding new exhibition: " . $data->name);
	$shortName = "wystawa-" . $data->date;
	$stmt = $db->prepare("INSERT INTO wystawa (pelna_nazwa, nazwa, rodzaj, miejscowosc, oddzial, data, creator, created, changed, changed_by, zgloszenia_otwarte)
							VALUES (:fullExName, :name, :rang, :city, :department, :date, :createdBy, NOW(), NOW(), :createdBy, :status);");
	$stmt->bindParam(':fullExName', $data->name);
	$stmt->bindParam(':name', $shortName);
	$stmt->bindParam(':rang', $data->rang);
	$stmt->bindParam(':city', $data->city);
	$stmt->bindParam(':department', $data->department);
	$stmt->bindParam(':date', $data->date);
	$stmt->bindParam(':createdBy', $userId);
	$stmt->bindParam(':status', $data->status);
	$stmt->execute();

	return true;
}

function UpdateExhibition($data, $db, $log, $userId)
{
	if(!CheckUpdateExhibitionData($data))
		return false;
	
	$log -> addInfo("Updating exhibition: " . $data->name);
	$shortName = "wystawa" . $data->date;
	$stmt = $db->prepare("Update wystawa set pelna_nazwa = :fullExName, nazwa = :name, rodzaj = :rang, miejscowosc = :city, oddzial = :department, data = :date,
						changed = NOW(), changed_by = :createdBy, zgloszenia_otwarte = :status where id_wystawa = :id");
	$stmt->bindParam(':fullExName', $data->name);
	$stmt->bindParam(':name', $shortName);
	$stmt->bindParam(':rang', $data->rang);
	$stmt->bindParam(':city', $data->city);
	$stmt->bindParam(':department', $data->department);
	$stmt->bindParam(':date', $data->date);
	$stmt->bindParam(':createdBy', $userId);
	$stmt->bindParam(':status', $data->status);
	$stmt->bindParam(':id', $data->id);
	$stmt->execute();

	return true;
}

function RemoveExhibition($id, $db, $log, $userId)
{
	$log -> addInfo("Removing exhibition: " . $id . " by " . $userId);
	$stmt = $db->prepare("Delete from Uczestnicy where wystawa_id = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$stmt = $db->prepare("Delete from wystawa where id_wystawa = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	return true;
}

function GetUser($id, $dbw)
{
	$stmt = $dbw->prepare("Select display_name from wp_users where id = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	
	$result = $stmt->fetch();
	
	return $result['display_name'];
}

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
			$order = 'u.fullname';
			break;
	}
	
	$stmt = $db->prepare("SELECT u.id, u.fullname as pe³na_nazwa, u.plec, u.data_ur as data_urodzenia, r.rasa, r.breed, r.grupa, m.masc, u.klasa, u.data_zg as data_zg³oszenia, u.tytul,
						u.wyszkolenie, u.nr_rod as nr_rodowodu, u.oznakowanie, u.sire as ojciec, u.dam as matka, u.hod_imie as imiê_hodowcy, u.hod_nazwisko as nazwisko_hodowcy,
						u.imie as imie_w³aœciciela, u.nazwisko as nazwisko_w³aœciciela, u.ulica, u.kod, u.miejscowosc as miejscowoœæ, u.region, u.panstwo as pañstwo, u.tel, u.email, 
						u.czlonek as cz³onek, u.testy_psych, u.reproduktor1, u.reproduktor2, u.reproduktor3, u.reproduktor4, u.reproduktor5, u.reproduktor6, u.suka1,  u.suka2, 
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

function GetApplicationConsts($db, $log)
{
	//get breeds
	$breeds = json_encode(GetBreeds($db));
	//get color
	$colors = json_encode(GetColors($db));
	//get countries
	$countries = json_encode(GetCountries($db));
	
	$result = '{"breed":' . $breeds . ',"color":' . $colors . ',"country":' . $countries . '}';

	return $result;
}

function AddParticipant($data, $db, $log, $userId)
{	
	$log -> addInfo("Adding new participant for exhibition: " . $data->exFullName);
	if(!checkExhibitionFormData($data))
		return "validation_error";
	
	if(applicationAlreadyExists($db, $data))
		return "already_exists";
	
	AddNewParticipant($data, $db, false, $userId);
		
	return true;
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