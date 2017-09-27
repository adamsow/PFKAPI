<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';

function GetMassMailConsts($db, $log)
{
	$log -> addInfo("Getting email consts");
    
    $departments = GetDepartments($db, $log);
    $exhibitions = GetExhibtionForMail($db);
    return '{"departments":' . $departments . ',"exhibitions":' . $exhibitions . '}';
}

function GetExhibtionForMail($db)
{
    $stmt = $db->prepare("SELECT id_wystawa AS id, pelna_nazwa AS name 
                          FROM wystawa 
                          WHERE data >  CURDATE() - 14 AND data < CURDATE();");

    $stmt->execute();
    $exhibitions = json_encode($stmt->fetchAll());

    return $exhibitions;
}

function SendEmailToMembers($db, $data, $log)
{
    $recipients;
    switch ($data->from) {
        case 'kontakt@pfk.org.pl':
            $password = 'Lp89!Mens511';
            $fromName = 'Kontakt PFK';
            break;
        case 'wystawy@pfk.org.pl':
            $password = '!?abcTUPO657?';
            $fromName = 'Wystawy PFK';
            break;
        default:
            $password = 'Lp89!Mens511';
            $fromName = 'Kontakt PFK';
            break;
    }

    switch ($data->to) {
        case 'allMembers':
            $recipients = GetAllMembersRecipients($db, $data->condition, 'all');
            break;
        case 'allBreedings':
            $recipients = GetAllBreedingsRecipients($db, $data->condition);
            break;
        case 'exhibitionMembers':
            $recipients = GetExhibitionMemebersRecipients($db, $data->condition);
            break;
        default:
            $recipients = GetAllMembersRecipients($db, $data->condition, $data->to);
            # code...
            break;
    }

    foreach ($recipients as $recipient) {
        SendEmail($recipient['email'], 
        $recipient['fullname'], 
        $data->body, 
        $data->subject, 
        $data->from, 
        $fromName,
        $password, true);
    }

    return "OK";
}

function GetAllMembersRecipients($db, $condition, $to)
{
    if ($to == 'all') {
        $cond = '';
    }
    else{
        $cond = "AND cz.przynaleznosc = '" . $to . "'";
    }
    $stmt = $db->prepare("SELECT cz.skladka as fee, o.email, CONCAT(o.imie, ' ', o.nazwisko) as fullname 
                        FROM czlonek cz
                        JOIN logowanie l on l.nr_leg = cz.nr_leg
                        JOIN osoba o on o.czlonek = cz.nr_leg
                        WHERE l.status = 'aktywny' and o.email is not null and o.email <> '' 
                        "  . $cond . ";");
    
    $stmt->execute();
    $members = $stmt->fetchAll();
    $membersToEmail = GetMembersToEmail($members, $condition);

    return $membersToEmail;
}

function GetAllBreedingsRecipients($db, $condition)
{
    $stmt = $db->prepare("SELECT cz.skladka as fee, o.email, CONCAT(o.imie, ' ', o.nazwisko) as fullname 
                        FROM czlonek cz
                        JOIN czlonek_hodowla czh on czh.nr_leg = cz.nr_leg
                        JOIN logowanie l on l.nr_leg = cz.nr_leg
                        JOIN osoba o on o.czlonek = cz.nr_leg
                        WHERE l.status = 'aktywny' and o.email is not null and o.email <> '';");
    
    $stmt->execute();
    $members = $stmt->fetchAll();
    $membersToEmail = GetMembersToEmail($members, $condition);
    

    return $membersToEmail;
}

function  GetExhibitionMemebersRecipients($db, $condition)
{
    $stmt = $db->prepare("SELECT email, CONCAT(imie, ' ', nazwisko) as fullname 
                        FROM Uczestnicy 
                        WHERE wystawa_id = :id and email is not null and email <> '';");

    $stmt->bindParam(':id', $condition);
    $stmt->execute();
    $members = $stmt->fetchAll();
    $membersToEmail = GetMembersToEmail($members, $condition);


    return $membersToEmail;
}

function GetMembersToEmail($members, $condition)
{
    $membersToEmail = array();
    $currentYear = date("Y");
    foreach ($members as $member) {
        switch ($condition) {
            case 'activeWithNonActualFee':
                if ($member['fee'] != 'zwolniony' && $member['fee'] != $currentYear) {
                    array_push($membersToEmail, $member);
                }
                break;
            case 'activeWithActualFee':
                if ($member['fee'] == 'zwolniony' || $member['fee'] == $currentYear) {
                    array_push($membersToEmail, $member);
                }
                break;
            default:
                array_push($membersToEmail, $member);
                break;
        }
    }

    return $membersToEmail;
}