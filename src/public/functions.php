<?php
use \Firebase\JWT\JWT;

function GetBreedings($db, $log)
{
	$log -> addInfo("Getting breedings.");
	$stmt = $db->prepare("SELECT grupa, o.imie, o.nazwisko, o.miejscowosc, o.region, o.email, o.tel_kom, o.tel_stac, h.przydomek, h.pisany, h.data_rej, r.rasa, r.breed FROM osoba o LEFT JOIN czlonek_hodowla ch ON o.czlonek = ch.nr_leg LEFT JOIN czlonek cz ON o.czlonek = cz.nr_leg LEFT JOIN logowanie l ON o.czlonek = l.nr_leg LEFT JOIN hodowla h ON ch.nr_hod = h.nr_hod LEFT JOIN rasa_hodowla rh ON h.nr_hod = rh.nr_hod LEFT JOIN rasa r ON rh.id_rasa = r.id_rasa WHERE czlonek IS NOT NULL AND ch.nr_leg IS NOT NULL AND l.status != 'blokada' AND (cz.data_stop IS NULL OR cz.data_stop = '') AND rasa IS NOT NULL AND (h.data_wrej IS NULL OR h.data_wrej = '') ORDER BY grupa, r.rasa, h.przydomek, o.nazwisko, o.imie;");
	$stmt->execute();
	$breedings = $stmt->fetchAll();
	
	return json_encode($breedings);
}

function GetStuds($db, $log)
{
	$log -> addInfo("Getting studs.");
	$stmt = $db->prepare("SELECT grupa, p.fullname, p.tytul, p.wyszkolenie, p.data_ur, r.rasa, r.breed, m.masc, m.colour, o.imie, o.nazwisko, o.email, o.tel_kom, o.tel_stac FROM pies p LEFT JOIN rasa r ON p.rasa = r.id_rasa LEFT JOIN masc m ON p.masc = m.id_masc LEFT JOIN wlasciciel_pies wp ON wp.id_pies = p.id_pies LEFT JOIN osoba o ON o.id_osoba = wp.id_osoba LEFT JOIN czlonek cz ON o.czlonek = cz.nr_leg LEFT JOIN logowanie l ON o.czlonek = l.nr_leg WHERE o.czlonek IS NOT NULL AND l.status != 'blokada' AND (cz.data_stop IS NULL OR cz.data_stop = '') AND r.rasa IS NOT NULL AND (p.data_sm IS NULL OR p.data_sm = '') AND plec = 'pies' AND (nadane_upr = 'pelne' OR nadane_upr = 'rok') ORDER BY grupa, r.rasa, p.fullname, o.nazwisko, o.imie;");
	$stmt->execute();
	$studs = $stmt->fetchAll();
	
	return json_encode($studs);
}

function GetLitters($db, $log)
{
	$log -> addInfo("Getting litters.");
	$stmt = $db->prepare("SELECT grupa, h.przydomek as hodowla, m.data_ur, r.rasa, r.breed FROM osoba o LEFT JOIN czlonek_hodowla ch ON o.czlonek = ch.nr_leg LEFT JOIN czlonek cz ON o.czlonek = cz.nr_leg LEFT JOIN logowanie l ON o.czlonek = l.nr_leg LEFT JOIN hodowla h ON ch.nr_hod = h.nr_hod LEFT JOIN miot m ON m.przydomek = h.nr_hod LEFT JOIN rasa r ON m.rasa = r.id_rasa WHERE ch.nr_leg IS NOT NULL AND l.status != 'blokada' AND (cz.data_stop IS NULL OR cz.data_stop = '') AND r.rasa IS NOT NULL AND (h.data_wrej IS NULL OR h.data_wrej = '') AND m.data_ur > CURDATE() - INTERVAL 7 MONTH GROUP BY m.nr_miot ORDER BY grupa, r.rasa, m.data_ur desc, h.przydomek;");
	$stmt->execute();
	$litters = $stmt->fetchAll();
	
	return json_encode($litters);
}

function GetExhibitionData($db, $log)
{
	$log -> addInfo("Getting exhibition data for form.");
	//get exhibitions
	$stmt = $db->prepare("SELECT nazwa as id, pelna_nazwa as name from wystawa where data >= CurDate() + interval 3 day AND data <= CurDate() + interval 91 day order by data;");
	$stmt->execute();
	$exhibitions = json_encode($stmt->fetchAll());
	//get breeds
	$stmt = $db->prepare("SELECT id_rasa as id, rasa as name FROM rasa ORDER BY rasa;");
	$stmt->execute();
	$breeds = json_encode($stmt->fetchAll());
	//get color
	$stmt = $db->prepare("SELECT id_masc as id, masc as name FROM masc ORDER BY masc;");
	$stmt->execute();
	$colors = json_encode($stmt->fetchAll());
	//get countries
	$stmt = $db->prepare("SELECT id_panstwo as id, kraj as name FROM panstwo;");
	$stmt->execute();
	$countries = json_encode($stmt->fetchAll());
	
	$result = '{"exhibition":' . $exhibitions . ',"breed":' . $breeds . ',"color":' . $colors . ',"country":' . $countries . '}';

	return $result;
}

function GetToken($username, $password, $dbw, $log, $secret)
{
	require_once('wp-functions.php');
	require_once('../vendor/firebase/php-jwt/src/JWT.php');
	require_once('../vendor/tuupola/slim-jwt-auth/src/JwtAuthentication.php');

	$log -> addInfo("Getting token for user: " . $username);
	$stmt = $dbw->prepare("SELECT wpu.user_pass, wpu.id, wpum.meta_value from wp_users wpu join wp_usermeta wpum on wpum.user_id = wpu.id where (wpu.user_nicename=:username or wpu.user_email=:username) and meta_key ='wp_capabilities'");
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

function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string ($d)) {
        return utf8_encode($d);
    }
    return $d;
}