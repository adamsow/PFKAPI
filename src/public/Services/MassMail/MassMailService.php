<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';

function GetMassMailConsts($db, $log)
{
	$log -> addInfo("Getting email consts");
    
    $departments = GetDepartments($db, $log);
    $exhibitions = GetExhibtionForMailAndSMS($db);
    return '{"departments":' . $departments . ',"exhibitions":' . $exhibitions . '}';
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
        if ($recipient['email'] !== null && $recipient['email'] != '') {
            SendEmail($recipient['email'], 
            $recipient['fullname'], 
            $data->body, 
            $data->subject, 
            $data->from, 
            $fromName,
            $password, true);
        }
    }

    return "OK";
}

