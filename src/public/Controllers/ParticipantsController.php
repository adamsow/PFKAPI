<?php
    require_once('../APIConfiguration.php');
    require_once('../Services/Participants/ParticipantService.php');

    //GET exhibition participants 
    $app->get('/participants/:id', $referer($app), $authorization($app), function ($id) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $scope = $app->jwt->scope;
        $access = GetAccess($scope, $app->pages['sites']['exhibitions'], $db, $log);
        $participants = GetExhibitionParticipants($db, $log, $id);
        echo '{"access":"' . $access . '","participants":' . $participants . '}';
    });

    //GET info about particiapnt 
    $app->get('/participantById/:id', $referer($app), $authorization($app), function ($id) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $participant = GetExhibitionParticipant($db, $log, $id, $dbw);
        echo $participant;
    });

    //add new participant
    $app->post('/participants', $referer($app), $writeAccess($app), function () use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        $result = AddParticipant($data, $db, $log, $userId);
        echo $result;
    });

    //update existing participant 
    $app->put('/participants/:id', $referer($app), $writeAccess($app), function ($id) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $body = $app->request->getBody();
        $userId = $app->jwt->user_id;
        $data = json_decode($body);
        $result = UpdateParticipant($id, $data, $db, $log, $userId);
        echo $result;
    });

    //Delete participant 
    $app->delete('/participants/:id', $referer($app), $allAccess($app), function ($id) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $scope = $app->jwt->scope;
        $userId = $app->jwt->user_id;
        $result = RemoveParticipant($id, $db, $log, $userId);
        echo $result;
    });

    //GET all info for exhibition (for csv files) 
    $app->get('/participantsAll/:id/:filter', $referer($app), $authorization($app), function ($id, $filter) use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $participants = GetExhibitionParticipantsAll($db, $log, $id, $filter, $dbw);
        echo $participants;
    });

    //GET consts for appplication form
    $app->get('/applicationConsts', $referer($app), $authorization($app), function () use ($app) 
    {
        $log = $app->log;
        $db = $app->db;
        $dbw = $app->dbw;
        $consts = GetApplicationConsts($db, $log);
        echo $consts;
    });

$app->run();