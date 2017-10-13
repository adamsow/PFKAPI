<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';
require_once __DIR__ . '/../../FPDF/fpdf.php';
//require_once __DIR__ . '/../../../../../wp-load.php';
require_once('ValidateMemberService.php');

function GetDepartmentMembers($db, $log, $department, $filter){
	$log -> addInfo("Getting members for department: " . $department);
	switch($filter)
	{
		case "all":
			$condition = "1=1";
			break;
		case "active":
			$condition = "(l.status = 'aktywny' OR l.status = 'niepotwierdzony')";
			break;
		case "not-active":
			$condition = "(l.status = 'blokada')";
			break;
		default:
			$condition = '';
			break;
	}
	if($department == 'baza-czlonkow')
	{
		$stmt = $db->prepare("SELECT cz.nr_leg as Id, cz.data_przys as startDate, o.imie as name, o.nazwisko as surname, o.miejscowosc as city, cz.skladka as paid,
					o.poprawnosc as accepted, GROUP_CONCAT(h.przydomek SEPARATOR ', ') as breeding, cz.przynaleznosc as department
					FROM czlonek cz
					JOIN osoba o on o.czlonek = cz.nr_leg
					JOIN logowanie l on l.nr_leg = cz.nr_leg
          			LEFT JOIN czlonek_hodowla czh on czh.nr_leg = cz.nr_leg
          			LEFT JOIN hodowla h on h.nr_hod = czh.nr_hod
					WHERE " . $condition . "
          			GROUP BY cz.nr_leg;");
	}
	else
	{
		$stmt = $db->prepare("SELECT cz.nr_leg as Id, cz.data_przys as startDate, o.imie as name, o.nazwisko as surname, o.miejscowosc as city, cz.skladka as paid,
						  o.poprawnosc as accepted
						  FROM czlonek cz
						  JOIN osoba o on o.czlonek = cz.nr_leg
						  JOIN logowanie l on l.nr_leg = cz.nr_leg
						  WHERE przynaleznosc = :department AND " . $condition . ";");
	}
	$stmt->bindParam(':department', $department);
	$stmt->execute();
	$members = json_encode($stmt->fetchAll());
	
	return $members;
}

function GetMemberConsts($db, $log)
{
    $departments = GetDepartments($db, $log);
    $countries = json_encode(GetCountries($db));
    return '{"departments":' . $departments . ',"countries":' . $countries . '}';
}

function UserExists($email)
{
	if (username_exists($email)) {
		return true;
	}
	if (email_exists($email)) {
		return true;
	}
	
	return false;
}

function PersonExists($db, $data, $id)
{
	$stmt = $db->prepare("SELECT count(*) 
						FROM osoba 
						WHERE imie = :name and nazwisko = :surname and miejscowosc = :city
						AND (:id is null OR czlonek <> :id)");

	$stmt->bindParam(':name', $data->name);
	$stmt->bindParam(':surname', $data->surname);
	$stmt->bindParam(':city', $data->city);
	$stmt->bindParam(':id', $id);

	$stmt->execute();

	$number_of_rows = $stmt->fetchColumn(); 

	if($number_of_rows > 0)
		return true;

	return false;
}

function AddMember($data, $db, $log, $userId, $dbw)
{
	if(!checkMemberData($data))
		return 0;
	if(!UserExists($data->email))
	{
		CreateUser($data, $dbw);
	}
	else
	{
		UpdateRole($data);
	}

	$log -> addInfo("Adding member for department: " . $data->department);	

	//PHOTO is no longer needed
	// $stmt = $db->prepare("INSERT INTO photo (copyright, tytul, posted_by) 
	// 					  VALUES (:copyright, :title, :userId);");

	// $stmt->bindParam(':copyright', $data->copyright);
	// $stmt->bindParam(':title', $data->photoTilte);
	// $stmt->bindParam(':userId', $userId);

	// $stmt->execute();

	// $photoId = $db->lastInsertId();

	$stmt = $db->prepare("INSERT INTO czlonek (data_ur, data_przys, skladka, przynaleznosc, funkcje, opis, adnotacje, creator, 
						  created, changed_by, changed) 
						  VALUES (:birthDate, NOW(), :fee, :department, :functions, :characteristic, :additionalInfo, :userId, 
						  NOW(), :userId, NOW());");
	
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':fee', $data->fee);
	$stmt->bindParam(':department', $data->department);
	$stmt->bindParam(':functions', $data->functions);
	$stmt->bindParam(':characteristic', $data->characteristic);
	$stmt->bindParam(':additionalInfo', $data->additionalInfo);
	$stmt->bindParam(':userId', $userId);
	//$stmt->bindParam(':photoId', $photoId);
	//$stmt->bindParam(':photoId', null);

	$stmt->execute();
	$id = $db->lastInsertId();

	$stmt = $db->prepare("INSERT INTO osoba (imie, nazwisko, ulica, kod, miejscowosc, region, panstwo, tel_stac, tel_kom, email, 
					      czlonek, creator, created, changed_by, changed, poprawnosc) 
					      VALUES (:name, :surname, :street, :postal, :city, :voivo, :country, :phone, :mobile, :email, :czlonekId, 
						  :userId, NOW(), :userId, NOW(), :accepted);
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
	$stmt->bindParam(':czlonekId', $id);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':accepted', $data->accepted);

	$stmt->execute();

	$stmt = $db->prepare("INSERT INTO logowanie (nr_leg, status) 
						  VALUES (:id, :status);");

	$stmt->bindParam(':id', $id);
	$stmt->bindParam(':status', $data->status);

	$stmt->execute();

	return $id;
}

function CreateUser($data, $dbw)
{
	// Generate the password and create the user
	$password = wp_generate_password( 12, false );
	$userId = wp_create_user($data->email, $password, $data->email);
	
	// Set the nickname
	wp_update_user(
		array(
		'ID'          =>    $userId,
		'nickname'    =>    $data->email,
		'first_name'  =>	$data->name,
		'last_name'	  =>	$data->surname,
		'role'		  =>	'czonek'
		)
	);

	// Set the role
	$user = new WP_User( $userId );
	$user->set_role( 'czonek' );

	$message = PrepareAccountCreationMessage($data->email, $password);
	$headers = array('Content-Type: text/html; charset=UTF-8');
	
	// Email the user
	wp_mail( $data->email, 'Witamy w PFK!', $message, $headers );
	//send activation link

	$stmt = $dbw->prepare("INSERT INTO wp_usermeta (meta_key, meta_value, user_id) 
						  VALUES ('sbwev-approve-user', '1', :userId);");

	$stmt->bindParam(':userId', $userId);

	$stmt->execute();
}

function UpdateRole($data)
{
	$user = get_user_by("email", $data->email);
	$userId = $user->ID;
	$user = new WP_User( $userId );
	$user->set_role( 'czonek' );
}

function UpdateMember($data, $db, $log, $userId, $memberId, $changeEmail, $oldEmail)
{
	if (!checkMemberData($data)) {
		return 0;
	}
	if ($changeEmail) {
		UdpateEmail($data->email, $oldEmail);
	}

	$log -> addInfo("Updating member: " . $memberId);	

	//PHOTO is no longer needed
	// //GET photo id
	// $photoId = GetPhotoId($memberId, $db);
	// //Update photo
	// if ($photoId > 0) {
	// 	$stmt = $db->prepare("UPDATE photo set copyright = :copyright, tytul = :title, posted_by = :userId
	// 					  WHERE id_photo = :photoId;");

	// 	$stmt->bindParam(':copyright', $data->copyright);
	// 	$stmt->bindParam(':title', $data->photoTilte);
	// 	$stmt->bindParam(':userId', $userId);
	// 	$stmt->bindParam(':photoId', $photoId);
	
	// 	$stmt->execute();
	// }
	// else{
	// 	$stmt = $db->prepare("INSERT INTO photo (copyright, tytul, posted_by) 
	// 					  VALUES (:copyright, :title, :userId);");

	// 	$stmt->bindParam(':copyright', $data->copyright);
	// 	$stmt->bindParam(':title', $data->photoTilte);
	// 	$stmt->bindParam(':userId', $userId);

	// 	$stmt->execute();

	// 	$photoId = $db->lastInsertId();

	// 	$stmt = $db->prepare("UPDATE czlonek set zdjecie = :photoId 
	// 					  	WHERE nr_leg = :memberId;");

	// 	$stmt->bindParam(':photoId', $photoId);
	// 	$stmt->bindParam(':memberId', $memberId);

	// 	$stmt->execute();
	// }
	

	// $stmt->bindParam(':copyright', $data->copyright);
	// $stmt->bindParam(':title', $data->photoTilte);
	// $stmt->bindParam(':photoId', $photoId);

	//$stmt->execute();

	$stmt = $db->prepare("UPDATE czlonek set data_ur = :birthDate, skladka = :fee, przynaleznosc = :department, funkcje = :functions,
						 opis = :characteristic, adnotacje = :additionalInfo, changed_by = :userId, changed = NOW(), 
						 data_stop = :removeDate
						 WHERE nr_leg = :memberId;");
	
	$stmt->bindParam(':birthDate', $data->birthDate);
	$stmt->bindParam(':fee', $data->fee);
	$stmt->bindParam(':department', $data->department);
	$stmt->bindParam(':functions', $data->functions);
	$stmt->bindParam(':characteristic', $data->characteristic);
	$stmt->bindParam(':additionalInfo', $data->additionalInfo);
	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':removeDate', $data->removeDate);
	$stmt->bindParam(':memberId', $memberId);

	$stmt->execute();

	$stmt = $db->prepare("UPDATE osoba set imie = :name, nazwisko = :surname, ulica = :street, kod = :postal, miejscowosc = :city, 
						region = :voivo, panstwo = :country, tel_stac = :phone, tel_kom = :mobile, email = :email, changed_by = :userId,
						changed = NOW(), poprawnosc = :accepted
						WHERE czlonek = :memberId;");

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
	$stmt->bindParam(':memberId', $memberId);
	$stmt->bindParam(':accepted', $data->accepted);

	$stmt->execute();

	$stmt = $db->prepare("UPDATE logowanie set status = :status 
						  WHERE nr_leg = :memberId;");

	$stmt->bindParam(':status', $data->status);
	$stmt->bindParam(':memberId', $memberId);

	$stmt->execute();
	
	return $memberId;
}

function UdpateEmail($email, $oldEmail)
{
	$user = get_user_by("email", $oldEmail);
	$args = array(
		'ID'         => $user->ID,
		'user_email' =>  $email,
	);
	$result = wp_update_user( $args );
}

function GetMemberById($db, $log, $id, $dbw)
{
	$log -> addInfo("Getting info about member: " . $id);	
	$stmt = $db->prepare("SELECT p.url as photoUrl, p.urlth as photoThumb, p.copyright, p.tytul as photoTilte, cz.data_ur as birthDate, l.status, o.imie as name,
						o.nazwisko as surname, o.miejscowosc as city, o.kod as postal, o.region as voivodeship, o.panstwo as country, o.email, o.tel_stac as phone,
						o.tel_kom as mobile, cz.skladka as fee, cz.przynaleznosc as department, cz.funkcje as functions, cz.opis as characteristic, 
						cz.adnotacje as additionalInfo, o.poprawnosc as accepted, cz.data_przys as startDate, cz.creator as created_by, cz.created,
						cz.changed_by, cz.changed as modified, cz.data_stop as removeDate, o.ulica as street
						FROM czlonek cz
						JOIN osoba o on o.czlonek = cz.nr_leg
						JOIN logowanie l on l.nr_leg = cz.nr_leg
						LEFT JOIN photo p on p.id_photo = cz.zdjecie
						WHERE cz.nr_leg = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$member = $stmt->fetch();
	$createdBy = GetUser($member['created_by'], $dbw);
	$modifiedBy = GetUser($member['changed_by'], $dbw);
	$member['created_by'] = $createdBy;
	$member['changed_by'] = $modifiedBy;
	
	//Get hodowle
	$stmt = $db->prepare("SELECT h.przydomek as breedingName, h.nr_hod as breedingId, h.data_wrej as unregisterDate
						FROM czlonek_hodowla czh  
						JOIN hodowla h on h.nr_hod = czh.nr_hod
						WHERE czh.nr_leg = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$breedings = $stmt->fetchAll();
	$member['breedings'] = $breedings;	

	//Get own dogs
	$stmt = $db->prepare("SELECT p.id_pies as dogId, p.fullname as dogName
						FROM pies p
						JOIN wlasciciel_pies wp on wp.id_pies = p.id_pies
						JOIN osoba o on o.id_osoba = wp.id_osoba
						JOIN czlonek cz on cz.nr_leg = o.czlonek
						where cz.nr_leg = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$ownDogs = $stmt->fetchAll();
	$member['ownDogs'] = $ownDogs;	

	//Get breeder dogs
	$stmt = $db->prepare("SELECT p.id_pies as dogId, p.fullname as dogName
						FROM pies p
						JOIN hodowca_pies wp on wp.id_pies = p.id_pies
						JOIN osoba o on o.id_osoba = wp.id_osoba
						JOIN czlonek cz on cz.nr_leg = o.czlonek
						where cz.nr_leg = :id;");
	
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$breederDogs = $stmt->fetchAll();
	$member['breederDogs'] = $breederDogs;	
	
	
	$member = json_encode($member);
	
	return $member;
}

function SavePhoto($db, $id)
{
	$photo = $_FILES['file'];
	$photoName = $photo['name'];
	$photoTmpName = $photo["tmp_name"];
	$uploads_dir = '/uploads';
	$ext = pathinfo($photoName, PATHINFO_EXTENSION);
	$name = sha1_file($_FILES['file']['tmp_name']);
	$name = $name . "." . $ext;
	$path = __DIR__ . '/../../../../../wp-content/uploads/MemberPhotos/';
	$result = move_uploaded_file($photoTmpName, $path . $name);
	$file = $path . $name;
	$thumbFile = $path . "thumbnails/" . $name;
	smart_resize_image($file , null, 200, 100, true, $thumbFile, false, false, 95, true);
	
	smart_resize_image($file , null, 500, 300, true, $file, true, false, 95, true);

	$url = "https://pfk.org.pl/wp-content/uploads/MemberPhotos/";
	$photoPath = $url . $name;
	$photoPathThumb = $url . "thumbnails/" . $name;

	$photoId = GetPhotoId($id, $db);

	$stmt = $db->prepare("UPDATE photo set url = :url, urlth = :thurl, posted = NOW()
						  WHERE id_photo = :id;");

	$stmt->bindParam(':url', $photoPath);
	$stmt->bindParam(':thurl', $photoPathThumb);
	$stmt->bindParam(':id', $photoId);
	
	$stmt->execute();
}

function GetPhotoId($id, $db)
{
	$stmt = $db->prepare("SELECT zdjecie as photoId from czlonek 
						WHERE nr_leg = :id;");
	$stmt->bindParam(':id', $id);
	
	$stmt->execute();
	$photoId = $stmt->fetch();
	$photoId = $photoId['photoId'];

	return $photoId;
}

function RemoveMember($id, $db, $log, $userId, $email)
{
	$log -> addInfo("Removing member: " . $id . " by " . $userId);
	$photoId = GetPhotoId($id, $db);
	
	$stmt = $db->prepare("DELETE FROM czlonek WHERE nr_leg = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM osoba WHERE czlonek = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM logowanie WHERE nr_leg = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM photo WHERE id_photo = :photoId;");
	$stmt->bindParam(':photoId', $photoId);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM hodowla WHERE nr_hod in (select nr_hod from czlonek_hodowla where nr_leg = :id);");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM czlonek_hodowla WHERE nr_leg = :id;");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM pies 
						WHERE id_pies in (select id_pies from hodowca_pies 
							where id_osoba = (select id_osoba from osoba where czlonek = :id));");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM hodowca_pies WHERE id_osoba = (select id_osoba from osoba where czlonek = :id);");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM pies 
						WHERE id_pies in (select id_pies from wlasciciel_pies 
							where id_osoba = (select id_osoba from osoba where czlonek = :id));");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	$stmt = $db->prepare("DELETE FROM wlasciciel_pies WHERE id_osoba = (select id_osoba from osoba where czlonek = :id);");
	$stmt->bindParam(':id', $id);
	$stmt->execute();

	//remove wordpress user 
	$user = get_user_by("email", $email);
	wp_delete_user( $user->ID );

	return true;
}

function GetCertificate($db, $log, $id, $dbw, $isBreeder)
{
	$member = json_decode(GetMemberById($db, $log, $id, $dbw));
	$pdf = CreateCertificate($db, $log, $id, $member, $dbw, $isBreeder);

	return $pdf->Output('S');
}

function SendCertificate($db, $log, $id, $dbw, $isBreeder)
{
	$member = json_decode(GetMemberById($db, $log, $id, $dbw));
	$pdf = CreateCertificate($db, $log, $id, $member, $dbw, $isBreeder);
	$attachment = $pdf->Output('S');
	$message = file_get_contents(__DIR__ . '/../../email-templates/zaswiadczenie.html');
	$message = $isBreeder ? str_replace("{SUBJECT}", "Hodowcy", $message) : str_replace("{SUBJECT}", "o członkostwie w PFK", $message);
	$subject = $isBreeder ? "Zaswiadczenie hodowcy" : "Zaswiadczenie o członkowstwie";
	
	 SendEmail($member->email, $member->name . " " . $member->surname, $message, 
	 	$subject, "kontakt@pfk.org.pl", "Kontakt PFK", "Lp89!Mens511", true, $attachment);
}

function CreateCertificate($db, $log, $id, $member, $dbw, $isBreeder)
{
	$pdf = new FPDF();
	$pdf->AddPage();
	$pdf->AddFont('arial2','','arial2.php');  //dodaje swoją czcionkę arialpl do dokumentu
	$pdf->SetFont('arial2','',12);
	$pdf->Image('https://pfk.org.pl/wp-content/uploads/pfk.png',60,10,90,0,'PNG',10,10,-300);
	$pdf->Image('https://pfk.org.pl/wp-content/uploads/pdf_background.png',-80,30,350,0,'PNG',10,10,-300);
	$pdf->SetXY(170, 40);
	$pdf->Cell(30,20,'Sieradz, dnia ' . date("d") . ' ' . iconv("utf-8", "iso-8859-2",GetPolishMonth(date('M'))) . ' ' . date('Y') . ' r.', 0, 1, 'R');
	$str = $member->name . " " . $member->surname . "\n" . $member->street . "\n" . $member->postal . " " . $member->city;
	$str = iconv("utf-8", "iso-8859-2", $str);
	$pdf->MultiCell(190,5,$str, 0, 'R');
	$pdf->SetXY(10, 70);
	$str = "ZAŚWIADCZENIE";
	$str = iconv("utf-8", "iso-8859-2", $str);
	$pdf->Cell(80);
	$pdf->Cell(60, 70,$str);
	$pdf->Ln();
	$str = $isBreeder ? "HODOWCY" : "o członkostwie";
	$str = iconv("utf-8", "iso-8859-2", $str);
	if($isBreeder)
	{
		$pdf->Cell(87);
	}
	else
	{
		$pdf->Cell(82);
	}
	$pdf->Cell(60, -60,$str);
	
	$pdf->Ln();
	$isMember = isset($member->removeDate) && $member->removeDate != '' ? "nie jest" : "jest";
	$currentYear = date("Y");
	$feeStatus = $member->fee == 'zwolniony' || $member->fee == $currentYear ? "nie zalega" : "zalega";
	$month = GetPolishMonth(date("M", strtotime($member->birthDate)));
	$breedings = '';
	$breedingsIds = '';
	$hasBreeding = ' oraz prowadzi';
	if ($isBreeder) {
		if (isset($member->breedings)) {
			foreach ($member->breedings as $breeding) {
				$breedingsIds .= GetBreedingId($breeding->breedingId) . ',';
				$breedings .= $breeding->breedingName . ',';
				if (isset($breeding->unregisterDate) && $breeding->unregisterDate != '') {
					$hasBreeding = ' oraz, że członek prowadził';
				}
			}
			$breedings = rtrim($breedings, ',');
			$breedingsIds = rtrim($breedingsIds, ',');
		}
	}

	$breeding = $isBreeder ? $hasBreeding ." hodowlę psów rasowych pod przydomkiem \"" . $breedings . "\" zarejestrowaną pod numerem " . $breedingsIds : "";
	$str = "Zarząd Główny Polskiej Federacji Kynologicznej z siedzibą w Sieradzu potwierdza, że: \r\n\r\n"
		. $member->name . " " . $member->surname . " ur. dnia " . date('d', strtotime($member->birthDate)) . " " . $month . " "  
		. date('Y', strtotime($member->birthDate)) . " r. na dzień wystawienia niniejszego zaświadczenia " . $isMember 
		. " członkiem stowarzyszenia i " . $feeStatus . " ze składkami za rok bieżący" . $breeding . ". Numer członkowski to " 
		. GetMemberId($id, $member->startDate) . ".";
	$str = iconv("utf-8", "iso-8859-2", $str);
	$pdf->SetXY(10, 120);
	$pdf->Write(5,$str);

	$str = "Zarząd Główny \r\n";
	$str = iconv("utf-8", "iso-8859-2", $str);
	$pdf->SetXY(166, 150);
	$pdf->Write(5,$str);
	$str = "Polskiej Federacji Kynologicznej";
	$str = iconv("utf-8", "iso-8859-2", $str);
	$pdf->SetXY(134, 155);
	$pdf->Write(5,$str);

	$pdf->SetFontSize(8);
	$str = "Niniejsze zaświadczenie wydaje się na prośbę zainteresowanego lub w związku z decyzją o wpisaniu na listę członków Polskiej Federacji Kynologicznej.\r\nDokument elektroniczny wydany zgodnie z postanowieniami statutu oraz innych przepisów obowiązujących w Polskiej Federacji Kynologicznej i nie wymaga podpisu.\r\nZaświadczenie jest ważne 14 dni, jednak nie dłużej niż na koniec roku bieżącego.";
	$str = iconv("utf-8", "iso-8859-2", $str);
	$pdf->SetXY(10, 240);
	$pdf->Write(5,$str);

	$pdf->SetTextColor(211,211,211);
	$str = "Polska Federacja Kynologiczna | ul. Lokajskiego 1/10 | 98-200 Sieradz | https://pfk.org.pl";
	$str = iconv("utf-8", "iso-8859-2", $str);
	$pdf->SetXY(50, 270);
	$pdf->Write(5,$str);

	return $pdf;
}

function GetMemberId($id, $start_date) {
	list($year, $month, $day) = explode("-", $start_date);
	$year = $year[2].$year[3];
	if($id>=100 and $id<1000) $id = "0".$id;
	elseif($id>=10 and $id<100) $id = "00".$id;
	elseif($id<10) $id = "000".$id;
	return $id."/".$year;
}

function GetPolishMonth( $m ){
	$m = date("M" , strtotime($m));
	$months = array( 'Jan' => 'stycznia', 'Feb' => 'lutego', 'Mar' => 'marca', 'Apr' => 'kwietnia', 
		'May' => 'maja', 'Jun' => 'czerwca', 'Jul' => 'lipca', 'Aug' => 'sierpnia','Sep' => "września", 'Sept' => "września", 
		'Oct' => 'października', 'Nov' => 'listopada', 'Dec' => 'grudnia');
	
	return $months[ $m ];
}

function GetBreedingId($id) {
	if($id>=100 and $id<1000) $id = "0".$id;
	elseif($id>=10 and $nr_hod<100) $id = "00".$id;
	elseif($id<10) $id = "000".$id;
	return "H".$id;
}