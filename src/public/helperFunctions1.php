<?php
function checkExhibitionFormData($data)
{
	require_once('regexFunctions.php');

	if($data->nickname == '' 
		|| checkFullname($data->nickname)===false
		|| $data->exhibition == ''
		|| $data->class == ''
		|| $data->sex == ''
		|| $data->birthDate == ''
		|| checkBirthDate($data->birthDate) === false
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