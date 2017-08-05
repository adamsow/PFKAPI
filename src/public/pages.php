<?php
$centrEx = '/Centrala/Wystawy';
return 
[
    'sites' => 
    [
        'exhibitions' => $centrEx, 
        '/exhibitions' => $centrEx, 
        '/participantById/:id' => $centrEx,
        '/participants/:id' => $centrEx,
        '/participants' => $centrEx,
        '/participantsAll/:id/:filter' => $centrEx,
        '/applicationConsts' => $centrEx,
        '/exhibitions/:filter' => $centrEx,
        '/exhibitionById/:id' => $centrEx,        
        '/exhibitions/:id' => $centrEx,        
        '/departments' => $centrEx     
    ]
];