<?php
require_once('regexFunctions.php');

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

function PrepareExhibiotnMessage($data)
{
	$message = iconv("Windows-1250", "UTF-8", file_get_contents('email-templates/wystawa.html'));
	
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

function SendEmail($to, $toName, $message, $subject, $from, $fromName, $password, $sendCopyToMe)
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

function GetBreeds($db)
{
	$stmt = $db->prepare("SELECT id_rasa as id, rasa as name FROM rasa ORDER BY rasa;");
	$stmt->execute();
	return $stmt->fetchAll();
}

function GetColors($db)
{
	$stmt = $db->prepare("SELECT id_masc as id, masc as name FROM masc ORDER BY masc;");
	$stmt->execute();
	return $stmt->fetchAll();
}	

function GetCountries($db)
{
	$stmt = $db->prepare("SELECT id_panstwo as id, kraj as name FROM panstwo;");
	$stmt->execute();
	return $stmt->fetchAll();
}

function AddNewParticipant($data, $db, $isFromForm, $userId)
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
	if($isFromForm)
	{
		$stmt->bindParam(':isAccepted', 'nie');
	}
	else
	{
		$stmt->bindParam(':isAccepted', $data->isAccepted);
	}
	
	$stmt->execute();
}