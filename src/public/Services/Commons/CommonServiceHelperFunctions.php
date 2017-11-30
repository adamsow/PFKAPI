<?php
require_once('regexFunctions.php');
require_once('resize_image.php');

function GetBreeds($db)
{
	$stmt = $db->prepare("SELECT id_rasa as id, rasa as name, breed, grupa as gr
						FROM rasa 
						ORDER BY rasa
						COLLATE utf8_polish_ci;");
	$stmt->execute();
	return $stmt->fetchAll();
}

function GetColors($db)
{
	$stmt = $db->prepare("SELECT id_masc as id, masc as name, colour as color 
						FROM masc 
						ORDER BY masc
						COLLATE utf8_polish_ci;");
	$stmt->execute();
	return $stmt->fetchAll();
}	

function GetCountries($db)
{
	$stmt = $db->prepare("SELECT id_panstwo as id, kraj as name FROM panstwo;");
	$stmt->execute();
	return $stmt->fetchAll();
}

function checkExhibitionFormData($data)
{
	if($data->nickname == '' 
		|| checkFullname($data->nickname)===false
		|| $data->exhibition == ''
		|| $data->class == ''
		|| $data->sex == ''
		|| $data->birthDate == ''
		|| checkIfDateInPastOrToday($data->birthDate) === false
		|| $data->breed == ''
		|| $data->color == ''
		|| ($data->titles != '' && checkTitle($data->titles) === false) 
		|| ($data->class == '7' && $data->titles == '')
		|| ($data->training != '' && checkTraining($data->training) === false)
		|| ($data->class == '6' && $data->training == '')
		|| $data->lineage == ''
		|| checkLineage($data->lineage) === false
		|| $data->marking == ''
		|| checkMarking($data->marking) === false
		|| $data->father == ''
		|| checkFullname($data->father) === false
		|| $data->mother == ''
		|| checkFullname($data->mother) === false
		|| $data->breederName == ''
		|| checkName($data->breederName) === false
		|| $data->breederSurname == ''
		|| checkFullname($data->breederSurname) === false
		|| $data->ownerName == ''
		|| checkName($data->ownerName) === false
		|| $data->ownerSurname == ''
		|| checkFullname($data->ownerSurname) === false
		|| $data->ownerStreet == ''
		|| checkStreet($data->ownerStreet) === false
		|| $data->ownerPostal == ''
		|| checkPostal($data->ownerPostal) === false
		|| $data->ownerCity == ''
		|| checkCity($data->ownerCity) === false
		|| $data->ownerVoivodeship == ''
		|| checkCity($data->ownerVoivodeship) === false
		|| $data->ownerCountry == ''
		|| ($data->ownerMobile != '' && checkMobile($data->ownerMobile) === false)
		|| ($data->additionalInfo != '' && checkAdditionalInfo($data->additionalInfo) === false)
		|| $data->ownerEmail == ''
		|| checkEmail($data->ownerEmail) === false
		|| $data->isMember == ''
		|| $data->psychoTest == ''
		|| ($data->sex == 'pies' && $data->isBestStud == 'true'
			&& (($data->stud1 == '' || checkFullname($data->stud1) === false)
			|| ($data->stud2 == '' || checkFullname($data->stud2) === false)
			|| ($data->stud3 == '' || checkFullname($data->stud3) === false)
			|| ($data->stud4 != '' && checkFullname($data->stud4) === false)
			|| ($data->stud5 != '' && checkFullname($data->stud5) === false)
			|| ($data->stud6 != '' && checkFullname($data->stud6) === false)))
		|| ($data->sex == 'suka' && $data->isBestBitch == 'true'
			&& (($data->bitch1 == '' || checkFullname($data->bitch1) === false)
			|| ($data->bitch2 == '' || checkFullname($data->bitch2) === false)
			|| ($data->bitch3 == '' || checkFullname($data->bitch3) === false)
			|| ($data->bitch4 != '' && checkFullname($data->bitch4) === false)
			|| ($data->bitch5 != '' && checkFullname($data->bitch5) === false)
			|| ($data->bitch6 != '' && checkFullname($data->bitch6) === false)))
		|| ($data->isBestPair == 'true'
			&& (($data->pair1 == '' || checkFullname($data->pair1) === false)
			|| ($data->pair2 == '' || checkFullname($data->pair2) === false)))
		|| ($data->isBestKennel == 'true'
			&& (($data->kennel1 == '' || checkFullname($data->kennel1) === false)
			|| ($data->kennel2 == '' || checkFullname($data->kennel2) === false)
			|| ($data->kennel3 == '' || checkFullname($data->kennel3) === false)
			|| ($data->kennel4 != '' && checkFullname($data->kennel4) === false)
			|| ($data->kennel5 != '' && checkFullname($data->kennel5) === false)
			|| ($data->kennel6 != '' && checkFullname($data->kennel6) === false)))
		)
		{
			return false;
		}
		
	return true;
}

function applicationAlreadyExists($db, $data)
{
	$stmt = $db->prepare("SELECT count(*) FROM Uczestnicy 
		WHERE wystawa_id = :id
		AND fullname = :fullname 
		AND plec = :sex 
		AND data_ur = :birthDate 
		AND rasa = :breed 
		AND hod_nazwisko = :breederSurname 
		AND nazwisko = :ownerSurname;");
	$stmt->bindParam(':id', $data->exhibition);
	$stmt->bindParam(':fullname', $data->nickname);
	$stmt->bindParam(':sex', $data->sex);
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':breed', $data->breed);
	$stmt->bindParam(':breederSurname', $data->breederSurname);
	$stmt->bindParam(':ownerSurname', $data->ownerSurname);
	
	$stmt->execute();
	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function GetClass($class)
{
	switch ($class) 
	{
		case "1":
			return 'baby';
		case "2":
			return 'szczeniat';
		case "3":
			return 'mlodziezy';
		case "4":
			return 'posrednia';
		case "5":
			return 'otwarta';
		case "6":
			return 'pracujaca';
		case "7":
			return 'championow';
		case "8":
			return 'weteranow';
		case "9":
			return 'uzytkowa';
		default:
			return 'baby';
	}
}

function PrepareExhibitionMessage($data)
{
	$message = iconv("Windows-1250", "UTF-8", file_get_contents(__DIR__ . '/../../email-templates/wystawa.html'));
	
	$bestStudYesNo = 'Nie';
	if($data->isBestStud == 'true'){
		$bestStudYesNo = 'Tak';
		$message = str_replace("{BestStudVisible}", "display:block;", $message);
	}
	else{
		$message = str_replace("{BestStudVisible}", "display:none;", $message);
	}
	$bestBitchYesNo = 'Nie';
	if($data->isBestBitch == 'true'){
		$bestBitchYesNo = 'Tak';
		$message = str_replace("{BestBitchVisible}", "display:block;", $message);
	}
	else{
		$message = str_replace("{BestBitchVisible}", "display:none;", $message);
	}
	$bestPairYesNo = 'Nie';
	if($data->isBestPair == 'true'){
		$bestPairYesNo = 'Tak';
		$message = str_replace("{BestPairVisible}", "display:block;", $message);
	}
	else{
		$message = str_replace("{BestPairVisible}", "display:none;", $message);
	}
	$bestKennelYesNo = 'Nie';
	if($data->isBestKennel == 'true'){
		$bestKennelYesNo = 'Tak';
		$message = str_replace("{BestKennelVisible}", "display:block;", $message);
	}
	else{
		$message = str_replace("{BestKennelVisible}", "display:none;", $message);
	}

	$message = str_replace("{Name}", $data->exFullName, $message);
	$message = str_replace("{Class}", $data->className, $message);
	$message = str_replace("{Nickname}", $data->nickname, $message);
	$message = str_replace("{Sex}", $data->sex, $message);
	$message = str_replace("{BirthDate}", $data->birthDate, $message);
	$message = str_replace("{Breed}", $data->breedName, $message);
	$message = str_replace("{Color}", $data->colorName, $message);
	$message = str_replace("{Lineage}", $data->lineage, $message);
	$message = str_replace("{Marking}", $data->marking, $message);
	$message = str_replace("{Titles}", $data->titles, $message);
	$message = str_replace("{Training}", $data->training, $message);
	$message = str_replace("{Father}", $data->father, $message);
	$message = str_replace("{Mother}", $data->mother, $message);
	$message = str_replace("{BreederName}", $data->breederName, $message);
	$message = str_replace("{BreederSurname}", $data->breederSurname, $message);
	$message = str_replace("{OwnerName}", $data->ownerName, $message);
	$message = str_replace("{OwnerSurname}", $data->ownerSurname, $message);
	$message = str_replace("{OwnerStreet}", $data->ownerStreet, $message);
	$message = str_replace("{OwnerCity}", $data->ownerCity, $message);
	$message = str_replace("{OwnerPostal}", $data->ownerPostal, $message);
	$message = str_replace("{OwnerVoivodeship}", $data->ownerVoivodeship, $message);
	$message = str_replace("{OwnerCountry}", $data->countryName, $message);
	$message = str_replace("{OwnerEmail}", $data->ownerEmail, $message);
	$message = str_replace("{OwnerMobile}", $data->ownerMobile, $message);
	$message = str_replace("{IsMember}", $data->isMember, $message);
	$message = str_replace("{PsychoTest}", $data->psychoTest, $message);
	$message = str_replace("{IsBestStud}", $bestStudYesNo, $message);
	$message = str_replace("{stud1}", $data->stud1, $message);
	$message = str_replace("{stud2}", $data->stud2, $message);
	$message = str_replace("{stud3}", $data->stud3, $message);
	$message = str_replace("{stud4}", $data->stud4, $message);
	$message = str_replace("{stud5}", $data->stud5, $message);
	$message = str_replace("{stud6}", $data->stud6, $message);
	$message = str_replace("{IsBestBitch}", $bestBitchYesNo, $message);
	$message = str_replace("{bitch1}", $data->bitch1, $message);
	$message = str_replace("{bitch2}", $data->bitch2, $message);
	$message = str_replace("{bitch3}", $data->bitch3, $message);
	$message = str_replace("{bitch4}", $data->bitch4, $message);
	$message = str_replace("{bitch5}", $data->bitch5, $message);
	$message = str_replace("{bitch6}", $data->bitch6, $message);
	$message = str_replace("{IsBestPair}", $bestPairYesNo, $message);
	$message = str_replace("{pair1}", $data->pair1, $message);
	$message = str_replace("{pair2}", $data->pair2, $message);
	$message = str_replace("{IsBestKennel}", $bestKennelYesNo, $message);
	$message = str_replace("{kennel1}", $data->kennel1, $message);
	$message = str_replace("{kennel2}", $data->kennel2, $message);
	$message = str_replace("{kennel3}", $data->kennel3, $message);
	$message = str_replace("{kennel4}", $data->kennel4, $message);
	$message = str_replace("{kennel5}", $data->kennel5, $message);
	$message = str_replace("{kennel6}", $data->kennel6, $message);
	
	return $message;
}

function PrepareAccountCreationMessage($email, $password)
{
	$message = iconv("Windows-1250", "UTF-8", file_get_contents(__DIR__ . '/../../email-templates/account.html'));
	
	$message = str_replace("{Username}", $email, $message);
	$message = str_replace("{Password}", $password, $message);
	
	return $message;
}

function SendEmail($to, $toName, $message, $subject, $from, $fromName, $password, $sendCopyToMe, $stringAttachment = '')
{
	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->CharSet = 'UTF-8';
	$mail->Host       = "pfk-sieradz.atthouse.pl"; // SMTP server example
	$mail->SMTPDebug  = 0;                     // enables SMTP debug information (for testing)
	$mail->SMTPAuth   = true;                  // enable SMTP authentication
	$mail->SMTPSecure = "ssl";
	$mail->Port       = 465;                    // set the SMTP port for the GMAIL server
	$mail->Username   = $from; // SMTP account username example
	$mail->Password   = $password;        // SMTP account password example
	$mail->SetFrom($from, $fromName);
	$mail->AddReplyTo($from, $fromName);
	$mail->Subject    = $subject;
	$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";
	$mail->MsgHTML($message);
	$mail->AddAddress($to, $toName);
	if ($stringAttachment !== '') {
		$mail->AddStringAttachment($stringAttachment, 'zaświadczenie o członkowstwie.pdf');
	}
	//if $sendCopyToMe == true, send email to from
	if($sendCopyToMe)
	{
		$mail->AddAddress($from, $from);
	}
	$mail->Send();	
}

function CheckNewExhibitionData($data)
{
	if($data->city == '' 
		|| checkCity($data->city)===false
		|| $data->name == ''
		|| checkExhibitionName($data->name) === false
		|| $data->rang == ''
		|| $data->status == ''
		|| $data->department == ''
		|| $data->date == ''
		)
		{
			return false;
		}
		
	return true;
}

function CheckUpdateExhibitionData($data)
{
	if($data->id == '' 
		|| !CheckNewExhibitionData($data)
		)
		{
			return false;
		}
		
	return true;
}

function GetUser($id, $dbw)
{
	$stmt = $dbw->prepare("Select display_name from wp_users where id = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$result = $stmt->fetch();

	$name = $result['display_name'] == null ? ' ' : $result['display_name'];
	return $name;
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

function GetDepartments($db, $log)
{
	$log -> addInfo("Getting departments.");
	$stmt = $db->prepare("SELECT id_region as id, oddzial as department from region;");
	$stmt->execute();
	$departments = json_encode($stmt->fetchAll());
	
	return $departments;
}

function GetExhibtionForMailAndSMS($db)
{
    $stmt = $db->prepare("SELECT id_wystawa AS id, pelna_nazwa AS name 
                          FROM wystawa 
                          WHERE data >  CURDATE() - interval 14 day AND data < CURDATE();");

    $stmt->execute();
    $exhibitions = json_encode($stmt->fetchAll());

    return $exhibitions;
}

function CheckFile()
{
	// Undefined | Multiple Files | $_FILES Corruption Attack
	// If this request falls under any of them, treat it invalid.
	if (!isset($_FILES['file']['error']) 
		|| is_array($_FILES['file']['error'])) 
	{
		throw new RuntimeException('Invalid parameters.');
	}

	// Check $_FILES['upfile']['error'] value.
	switch ($_FILES['file']['error']) 
	{
		case UPLOAD_ERR_OK:
			break;
		case UPLOAD_ERR_NO_FILE:
			throw new RuntimeException('No file sent.');
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			throw new RuntimeException('Exceeded filesize limit.');
		default:
			throw new RuntimeException('Unknown errors.');
	}

	// You should also check filesize here. 
	if ($_FILES['file']['size'] > 1048576) 
	{
		throw new RuntimeException('Exceeded filesize limit.');
	}

	// DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
	// Check MIME Type by yourself.
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	if (false === $ext = array_search(
		$finfo->file($_FILES['file']['tmp_name']),
		array(
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
		),
		true
	)) {
		throw new RuntimeException('Invalid file format.');
	}

	return true;
}

function CheckIfPersonExists($db, $data)
{
	$stmt = $db->prepare("SELECT count(*) 
						FROM osoba 
						WHERE imie = :name and nazwisko = :surname and miejscowosc = :city");
	
	$stmt->bindParam(':name', $data->name);
	$stmt->bindParam(':surname', $data->surname);
	$stmt->bindParam(':city', $data->city);

	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;
	
	return false;
}

function GetBreedsAutoComplete($db, $filter)
{
	$filter = '%' . $filter . '%';
	$stmt = $db->prepare("SELECT id_rasa as breedId, rasa as breed_pl, breed
						FROM rasa
						WHERE rasa LIKE :filter OR breed LIKE :filter;");
	
	$stmt->bindParam(':filter', $filter);
	
	$stmt->execute();
	$breeds = $stmt->fetchAll();
	return json_encode($breeds);
}

function GetColorsAutoComplete($db, $filter)
{
	$filter = '%' . $filter . '%';
	$stmt = $db->prepare("SELECT id_masc as colorId, masc as color_pl, colour as color
						FROM masc
						WHERE masc LIKE :filter OR colour LIKE :filter;");
	
	$stmt->bindParam(':filter', $filter);
	
	$stmt->execute();
	$colors = $stmt->fetchAll();
	return json_encode($colors);
}

function GetBreedingsAutoComplete($db, $filter)
{
	$filter = '%' . $filter . '%';
	$stmt = $db->prepare("SELECT h.nr_hod as breedingId, h.przydomek as breeding, cz.przynaleznosc as ownerDepartment,
						GROUP_CONCAT(CONCAT(o.imie, ' ', o.nazwisko) SEPARATOR ', ') as ownerName,
						GROUP_CONCAT(cz.przynaleznosc SEPARATOR ', ') as ownerDepartment,
						GROUP_CONCAT(cz.nr_leg SEPARATOR ', ') as ownerId
						FROM hodowla h
						JOIN czlonek_hodowla czh on czh.nr_hod = h.nr_hod
						JOIN czlonek cz on cz.nr_leg = czh.nr_leg
						JOIN osoba o on o.czlonek = cz.nr_leg
						WHERE przydomek LIKE :filter
						GROUP BY h.nr_hod;");
	
	$stmt->bindParam(':filter', $filter);
	
	$stmt->execute();
	$breedings = $stmt->fetchAll();
	return json_encode($breedings);
}

function GetDogsAutoComplete($db, $filter, $sex = '')
{
	switch ($sex) {
		case '':
			$condition = '';
			break;
		case 'pies':
			$condition = "and plec = 'pies'";
			break;
		case 'suka':
			$condition = "and plec = 'suka'";
			break;
		default:
			$condition = '';
			break;
	}

	$filter = '%' . $filter . '%';
	$stmt = $db->prepare("SELECT  p.id_pies as dogId, p.fullname as nickname, p.rasa as breedId, r.rasa as breedName, p.masc as colorId, m.masc as colorName,
        				p.nr_kkr as lineage, p.oznakowanie as marking, p.plec as sex, CONCAT(o.imie, ' ', o.nazwisko) as owner, o.ulica as ownerStreet,
        				o.miejscowosc as ownerCity, o.kod as ownerPostal, CONCAT(os.imie, ' ', os.nazwisko) as breeder, p.data_ur as birthDate,
						COALESCE(o.tel_kom, o.tel_stac) as mobile, COALESCE(cz.nr_leg, o.id_osoba) as ownerId, czl.nr_leg as breederId, cz.przynaleznosc as ownerDepartment,
						COALESCE(o.czlonek, 0) as isOwnerMember, czl.przynaleznosc as breederDepartment, COALESCE(os.czlonek, 0) as isBreederMember
						FROM pies p
            			LEFT JOIN wlasciciel_pies wp on wp.id_pies = p.id_pies
            			LEFT JOIN osoba o on o.id_osoba = wp.id_osoba
            			LEFT JOIN rasa r on r.id_rasa = p.rasa
            			LEFT JOIN masc m on m.id_masc = p.masc
            			LEFT JOIN hodowca_pies hp on hp.id_pies = p.id_pies
            			LEFT JOIN osoba os on os.id_osoba = hp.id_osoba
						LEFT JOIN czlonek cz on cz.nr_leg = o.czlonek
						LEFT JOIN czlonek czl on czl.nr_leg = os.czlonek
						WHERE fullname LIKE :filter " . $condition .";");
	
	$stmt->bindParam(':filter', $filter);
	
	$stmt->execute();
	$dogs = $stmt->fetchAll();
	return json_encode($dogs);
}

function GetPersonsAutoComplete($db, $filter)
{
	$filter = '%' . $filter . '%';
	$stmt = $db->prepare("SELECT COALESCE(cz.nr_leg, o.id_osoba) as Id, CONCAT(o.imie, ' ', o.nazwisko) as name, COALESCE(o.czlonek, 0) as isMember,
						cz.przynaleznosc as department, o.ulica as street, o.miejscowosc as city, o.kod as postal,
						COALESCE(o.tel_kom, o.tel_stac) as mobile
						FROM osoba o
						LEFT JOIN czlonek cz on cz.nr_leg = o.czlonek 
						WHERE o.imie LIKE :filter OR o.nazwisko LIKE :filter OR cz.nr_leg LIKE :filter
						OR CONCAT(o.imie, ' ', o.nazwisko) LIKE :filter OR CONCAT(o.nazwisko, ' ', o.imie) LIKE :filter;");
	
	$stmt->bindParam(':filter', $filter);
	
	$stmt->execute();
	$persons = $stmt->fetchAll();

	return json_encode($persons);
}

function GetAllMembersRecipients($db, $condition, $to)
{
    if ($to == 'all') {
        $cond = '';
    }
    else{
        $cond = "AND cz.przynaleznosc = '" . $to . "'";
    }
    $stmt = $db->prepare("SELECT cz.skladka as fee, o.email, CONCAT(o.imie, ' ', o.nazwisko) as fullname, o.tel_kom as mobile, cz.nr_leg as id 
                        FROM czlonek cz
                        JOIN logowanie l on l.nr_leg = cz.nr_leg
                        JOIN osoba o on o.czlonek = cz.nr_leg
                        WHERE l.status = 'aktywny' 
                        "  . $cond . ";");
    
    $stmt->execute();
    $members = $stmt->fetchAll();
    $membersTo = GetMembersToEmailAndSMS($members, $condition);

    return $membersTo;
}

function GetAllBreedingsRecipients($db, $condition)
{
    $stmt = $db->prepare("SELECT cz.skladka as fee, o.email, CONCAT(o.imie, ' ', o.nazwisko) as fullname, o.tel_kom as mobile, cz.nr_leg as id
                        FROM czlonek cz
                        JOIN czlonek_hodowla czh on czh.nr_leg = cz.nr_leg
                        JOIN logowanie l on l.nr_leg = cz.nr_leg
                        JOIN osoba o on o.czlonek = cz.nr_leg
                        WHERE l.status = 'aktywny';");
    
    $stmt->execute();
    $members = $stmt->fetchAll();
    $membersTo = GetMembersToEmailAndSMS($members, $condition);
    

    return $membersTo;
}

function  GetExhibitionMemebersRecipients($db, $condition)
{
    $stmt = $db->prepare("SELECT email, CONCAT(imie, ' ', nazwisko) as fullname, tel as mobile 
                        FROM Uczestnicy 
                        WHERE wystawa_id = :id;");

    $stmt->bindParam(':id', $condition);
    $stmt->execute();
    $members = $stmt->fetchAll();
    $membersTo = GetMembersToEmailAndSMS($members, $condition);


    return $membersTo;
}

function GetMembersToEmailAndSMS($members, $condition)
{
    $membersTo = array();
    $currentYear = date("Y");
    foreach ($members as $member) {
        switch ($condition) {
            case 'activeWithNonActualFee':
                if ($member['fee'] != 'zwolniony' && $member['fee'] != $currentYear) {
                    array_push($membersTo, $member);
                }
                break;
            case 'activeWithActualFee':
                if ($member['fee'] == 'zwolniony' || $member['fee'] == $currentYear) {
                    array_push($membersTo, $member);
                }
                break;
            default:
                array_push($membersTo, $member);
                break;
        }
    }

    return $membersTo;
}

function guidv4()
{
    if (function_exists('com_create_guid') === true)
        return trim(com_create_guid(), '{}');

    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function DeleteBreedingBreedConnection($db, $id)
{
	$stmt = $db->prepare("DELETE FROM rasa_hodowla WHERE nr_hod = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
}

function DeleteDogBreederConnectionByDogId($db, $id)
{
	$stmt = $db->prepare("DELETE FROM hodowca_pies WHERE id_pies = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
}

function DeleteDogOwnerConnectionByDogId($db, $id)
{
	$stmt = $db->prepare("DELETE FROM wlasciciel_pies WHERE id_pies = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
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

function CreateDogBreederConnection($db, $data, $id)
{
	$stmt = $db->prepare("INSERT into hodowca_pies (id_osoba, id_pies, changed)
						VALUES ((Case when :isBreederMember > 0 then 
						(select id_osoba from osoba where czlonek = :breeder) else :breeder end), :dogId, NOW());");
	$stmt->bindParam(':breeder', $data->breeder);
	$stmt->bindParam(':isBreederMember', $data->isBreederMember);
	$stmt->bindParam(':dogId', $id);
	$stmt->execute();
}

function CreateDogOwnerConnection($db, $data, $id)
{
	$stmt = $db->prepare("INSERT into wlasciciel_pies (id_osoba, id_pies, changed)
						VALUES ((Case when :isOwnerMember > 0 then 
						(select id_osoba from osoba where czlonek = :owner) else :owner end), :dogId, NOW());");
	$stmt->bindParam(':owner', $data->owner);
	$stmt->bindParam(':isOwnerMember', $data->isOwnerMember);
	$stmt->bindParam(':dogId', $id);
	$stmt->execute();
}

function GetLineageId($lineageNr, $breedGroup) {
	$grupar = array("I","II","III","IV","V","VI","VII","VIII","IX","X");
	$grupa = $grupar[$breedGroup - 1];
	$year = date('Y');
	$year = $year[2].$year[3];
	if($lineageNr>=100 and $lineageNr<1000) $lineageNr = "0".$lineageNr;
	elseif($lineageNr>=10 and $lineageNr<100) $lineageNr = "00".$lineageNr;
	elseif($lineageNr<10) $lineageNr = "000".$lineageNr;
	return $lineageNr.'/'.$grupa.'/'.$year;
}