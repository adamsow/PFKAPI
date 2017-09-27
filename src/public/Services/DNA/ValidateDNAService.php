<?php
require_once __DIR__ . '/../Commons/regexFunctions.php';
function checkDNAData($data)
{
    if($data->breedId == '' 
        ||$data->colorId == ''
        || $data->birthDate == ''
		|| checkIfDateInPastOrToday($data->birthDate) === false
		|| $data->fullname == ''
		|| checkFullname($data->fullname) === false
        || $data->lineage == ''
		|| checkLineage($data->lineage) === false
		|| $data->marking == ''
        || checkMarking($data->marking) === false
        || $data->breeder == ''
		|| checkFullname($data->breeder) === false
		|| $data->owner == ''
		|| checkFullname($data->owner) === false
		|| $data->ownerStreet == ''
		|| checkStreet($data->ownerStreet) === false
		|| $data->ownerPostal == ''
		|| checkPostal($data->ownerPostal) === false
		|| $data->ownerCity == ''
        || checkCity($data->ownerCity) === false
        || ($data->ownerMobile != '' && checkMobile($data->ownerMobile) === false)
        || $data->samplingDate == ''
        || checkIfDateInPastOrToday($data->samplingDate) === false
        || $data->prober == ''
		)
		{
			return false;
		}
		
	return true;
}