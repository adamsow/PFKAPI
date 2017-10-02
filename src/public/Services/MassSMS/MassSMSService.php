<?php
require_once __DIR__ . '/../Commons/CommonServiceHelperFunctions.php';

function GetMassSMSConsts($db, $log)
{
	$log -> addInfo("Getting sms consts");
    
    $departments = GetDepartments($db, $log);
    $exhibitions = GetExhibtionForMailAndSMS($db);
    return '{"departments":' . $departments . ',"exhibitions":' . $exhibitions . '}';
}


function SendSMS($db, $data, $log)
{
    $recipients;
    $to = '';

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

    $guid = guidv4();
    
    foreach ($recipients as $recipient) {
        if ($recipient['mobile'] !== null && $recipient['mobile'] != '') {
            $to .=  $recipient['mobile'] . ",";

            $stmt = $db->prepare("INSERT INTO SMSStatuses (fullname, number, sent_date, status, guid, message) 
                              VALUES (:fullname, :number, NOW(), 'przesłane do SMSAPI', :guid, :message);");
    
            $stmt->bindParam(':fullname', $recipient['fullname']);
            $stmt->bindParam(':number', $recipient['mobile']);
            $stmt->bindParam(':guid', $guid);
            $stmt->bindParam(':message', $data->body);
    
            $stmt->execute();
        }
    }

    $url = 'https://api.smsapi.pl/sms.do';
    $password = md5('Lp89!Mens511');
    $data = array('username' => 'kontakt@pfk.org.pl', 'password' => $password, 
        'from' => 'PFK', 'to' => $to, 'format' => 'json', 'message' => $data->body, 'encoding' => 'utf-8');
    
    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) 
    { 
	    $log -> addError("Error when trying send sms.");
        return "Error"; 
    }

    $result = json_decode($result);
    if(isset($result->error)) 
    {
	    $log -> addError("Error from SMSAPI. Error code: " . $result->error . ", Message: " . $result->message);
        return "Error";
    }

    foreach ($result->list as $sms)
    {
        $stmt = $db->prepare("UPDATE SMSStatuses set message_id = :messageId, status = 'Kolejka'
                            WHERE number = :number AND guid = :guid;");

        $stmt->bindParam(':messageId', $sms->id);
        $stmt->bindParam(':number', $sms->submitted_number);
        $stmt->bindParam(':guid', $guid);
        
        $stmt->execute();
    }

    return "OK";
}

function SaveSMSCallback($db, $log, $params)
{
    $messagesIds = explode(',', $params['messagesIds']);
    $statuses = explode(',', $params['statuses']);
    $statuses_names = explode(',', $params['statuses_names']);
    $to = explode(',', $params['to']);
    $date = explode(',', $params['date']);

    for ($i=0; $i < count($messagesIds); $i++) { 
        $done_date = date("Y/m/d G:i:s", $date[$i]);
        
        $stmt = $db->prepare("UPDATE SMSStatuses set status_code = :status_code, status = :status, 
                            done_date = :done_date
                            WHERE message_id = :message_id;");

        $stmt->bindParam(':status_code', $statuses[$i]);
        $stmt->bindParam(':status', $statuses_names[$i]);
        $stmt->bindParam(':done_date', $done_date);
        $stmt->bindParam(':message_id', $messagesIds[$i]);

        $stmt->execute();
    }    
}

function GetSMSReport($db, $data, $log)
{
    $log -> addInfo("Getting sms report.");
	
	$stmt = $db->prepare("SELECT fullname, number, status_code, status, sent_date, done_date, message
                        FROM SMSStatuses;");

	$stmt->execute();
    $sms_report = $stmt->fetchAll();
    foreach ($sms_report as $key => $sms) 
    {
        $sms_report[$key]['status'] = SetSMSStatus($sms['status_code']);
    }
	
	return json_encode($sms_report);
}


function SetSMSStatus($code)
{
    switch ($code) {
        case '404':
            return 'Dostarczona';
            break;
        case '401':
            return 'Nieznaleziona';
            break;
        case '402':
            return 'Przedawniona';
            break;
        case '403':
            return 'Wysłana';
            break;
        case '405':
            return 'Niedostarczona';
            break;
        case '406':
            return 'Nieudana';
            break;
        case '407':
            return 'Odrzucona';
            break;
        case '408':
            return 'Nieznany';
            break;
        case '409':
            return 'Kolejka';
            break;
        case '410':
            return 'Zaakceptowana';
            break;
        case '411':
            return 'Ponawianie';
            break;
        case '412':
            return 'Zatrzymanie';
            break;
        default:
            return 'Nieznany';
            break;
    }
}