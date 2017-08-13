<?php
$centrEx = '/Centrala/Wystawy';
$members = '/Centrala/Czlonkowie/';
$persons = '/Centrala/Czlonkowie/baza-osob';
$colors = '/Centrala/edycja-masci';
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
        '/departments' => $centrEx,
        'members/dolnośląski' => $members . 'dolnoslaski',
        'members/kujawsko-pomorski' => $members . 'kujawsko-pomorski',
        'members/lubelski' => $members . 'lubelski',
        'members/lubuski' => $members . 'lubuski',
        'members/mazowiecki' => $members . 'mazowiecki',
        'members/opolski' => $members . 'opolski',
        'members/podkarpacki' => $members . 'podkarpacki',
        'members/podlaski' => $members . 'podlaski',
        'members/pomorski' => $members . 'pomorski',
        'members/śląski' => $members . 'slaski',
        'members/warmińsko-mazurski' => $members . 'warminsko-mazurski',
        'members/zachodniopomorski' => $members . 'zachodniopomorski',
        'members/łódzki' => $members . 'lodzki',
        'members/wielkopolski' => $members . 'wielkopolski',
        'members/małopolski' => $members . 'malopolski',
        'members/świętokrzyski' => $members . 'swietokrzyski',
        'persons' => $persons,
        '/persons' => $persons,
        '/persons/:id' => $persons,
        '/personsConsts' => $persons,
        'colors' => $colors,
        '/colors' => $colors,
        '/colors/:id' => $colors,
    ]
];