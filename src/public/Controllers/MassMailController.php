<?php
	require_once('../APIConfiguration.php');
    require_once('../Services/MassMail/MassMailService.php');
    
    // Get departments data
    $app->get('/massmailconsts', $referer($app), $authorization($app), function() use ($app)
    {
        $log = $app->log;
        $db = $app->db;
        $scope = $app->jwt->scope;
        $access = GetAccess($scope, $app->pages['sites']['massmail'], $db, $log);
        $data = GetMassMailConsts($db, $log);
        echo '{"access":"' . $access .'", "data":' .$data .'}';

    });

    // Send email data
    $app->post('/sendemail', $referer($app), $writeAccess($app), function() use ($app)
    {
        $log = $app->log;
        $db = $app->db;
        $body = $app->request->getBody();
        $data = json_decode($body);
        $result = SendEmailToMembers($db, $data, $log);
        echo json_encode($result);

    });
 
    $app->run();
 