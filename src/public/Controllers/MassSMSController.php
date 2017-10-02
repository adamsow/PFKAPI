<?php
	require_once('../APIConfiguration.php');
    require_once('../Services/MassSMS/MassSMSService.php');
    
    // Get departments data
    $app->get('/masssmsconsts', $referer($app), $authorization($app), function() use ($app)
    {
        $log = $app->log;
        $db = $app->db;
        $scope = $app->jwt->scope;
        $access = GetAccess($scope, $app->pages['sites']['masssms'], $db, $log);
        $data = GetMassSMSConsts($db, $log);
        echo '{"access":"' . $access .'", "data":' .$data .'}';

    });

    // Send sms
    $app->post('/sendsms', $referer($app), $writeAccess($app), function() use ($app)
    {
        $log = $app->log;
        $db = $app->db;
        $body = $app->request->getBody();
        $data = json_decode($body);
        $result = SendSMS($db, $data, $log);
        echo $result;

    });
 
    //callback for SMSAPI
    $app->post('/smscallback',  function() use ($app)
    {
        $log = $app->log;
        $db = $app->db;
        $params = $app->request()->post();
        SaveSMSCallback($db, $log, $params);

        $app->response->write('OK');
    });

     // Send sms
     $app->get('/smsreport', $referer($app), $authorization($app), function() use ($app)
     {
         $log = $app->log;
         $db = $app->db;
         $body = $app->request->getBody();
         $data = json_decode($body);
         $result = GetSMSReport($db, $data, $log);
         echo $result;
 
     });

    $app->run();
 