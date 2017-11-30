<?php
$centrEx = '/Centrala/Wystawy';
$members = '/Centrala/Czlonkowie/';
$persons = '/Centrala/Czlonkowie/baza-osob';
$colors = '/Centrala/Edycja-masci';
$breeds = '/Centrala/Edycja-ras';
$breedings = '/Centrala/Hodowle';
$litters = '/Centrala/Mioty';
$DNA = '/Centrala/baza-dna';
$mail = '/Centrala/Korespondencja';
$SMS = '/Centrala/SMS';
$SMSReport = '/Centrala/raport-sms';
$myAccount = '/moje-pfk';
$lineages = '/Centrala/Rodowody';
$entryBook = '/Centrala/ksiega-wstepna';
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
        'members/baza-czlonkow' => $members . 'baza-czlonkow',
        'persons' => $persons,
        '/persons' => $persons,
        '/persons/:id' => $persons,
        '/personsConsts' => $persons,
        'colors' => $colors,
        '/colors' => $colors,
        '/colors/:id' => $colors,
        'breeds' => $breeds,
        '/breeds' => $breeds,
        '/breeds/:id' => $breeds,
        'breedings'=> $breedings,
        '/allBreedings/:filter'=> $breedings,
        '/getBreedingById/:id' => $breedings,
        '/newBreeding' => $breedings,
        '/updateBreeding/:id' => $breedings,
        '/deleteBreeding/:id' => $breedings,
        '/usersautocomplete/:filter' => $breedings,
        '/breedsautocompletebreedings/:filter' => $breedings,
        '/breederCertificate/:id/:token/:fullname' => $breedings,
        '/breederCertificateSend/:id' => $breedings,
        'litters' => $litters,
        '/litters' => $litters,
        '/litters/:id' => $litters,
        '/breedsautocompletelitters/:filter' => $litters,
        '/breedingsautocompletelitters/:filter' => $litters,
        '/dogsautocompletelitters/:filter/:sex' => $litters,
        'dna' => $DNA,
        '/dna' => $DNA,
        '/dna/:id' => $DNA,
        '/dogsautocompletedna/:filter' => $DNA,
        '/dnamembers' => $DNA,
        '/breedsautocompletedna/:filter' => $DNA,
        '/colorsautocompletedna/:filter' => $DNA,
        '/personsautocompletedna/:filter' => $DNA,
        'massmail' => $mail,
        '/massmailconsts' => $mail,
        '/sendemail' => $mail,
        'masssms' => $SMS,
        '/masssmsconsts' => $SMS,
        '/sendsms' => $SMS,
        '/smsreport' => $SMSReport,
        '/mydetails' => $myAccount,
        '/updatemydetails' => $myAccount,
        '/mydetailscertificate/:token/:fullname' => $myAccount,
        '/mybreedings' => $myAccount,
        '/updatemybreedings' => $myAccount,
        '/breedsautocompletemybreedings/:filter' => $myAccount,
        '/mybreedingscertificate/:token/:fullname' => $myAccount,
        '/mydogs' => $myAccount,
        '/mydogs/:id' => $myAccount,
        'lineages' => $lineages,
        '/dogswithlineage' => $lineages,
        '/dogswithlineage/:id' => $lineages,
        '/breedsautocompletedogswithlineage/:filter' => $lineages,
        '/colorsautocompletedogswithlineage/:filter' => $lineages,
        '/personsautocompletedogswithlineage/:filter' => $lineages,
        '/dogsautocompletedogswithlineage/:filter/:sex' => $lineages,
        '/dogswithlineage/:id' => $lineages,
        '/dogswithlineageshowlineage/:id/:generations' => $lineages,
        '/dogswithlineagesetparent' => $lineages,
        '/dogswithlineagedeleteparent/:childId/:isFather' => $lineages,
        '/dogsentrybook' => $entryBook,
        'entrybook' => $entryBook,
        '/breedsautocompletedogsentrybook/:filter' => $entryBook,
        '/colorsautocompletedogsentrybook/:filter' => $entryBook,
        '/personsautocompletedogsentrybook/:filter' => $entryBook,
        '/dogsautocompletedogsentrybook/:filter/:sex' => $entryBook,
        '/dogsentrybook/:id' => $entryBook,
    ]
];