<?php
require_once __DIR__ . '/../Commons/regexFunctions.php';
function checkDogData($data)
{
    if($data->breed == '' 
        ||$data->color == ''
        || $data->birthDate == ''
		|| checkIfDateInPastOrToday($data->birthDate) === false
		|| $data->nickname == ''
		|| checkFullname($data->nickname) === false
        || ($data->nrPedigree != '' && checkLineage($data->nrPedigree) === false)
		|| $data->marking == ''
        || checkMarking($data->marking) === false
        || $data->breeder == ''
        || $data->breed == ''
        || $data->color == ''
        || $data->breedingPermissions == ''
        || ($data->titles != '' && checkTitle($data->titles) === false)
        || ($data->training != '' && checkTraining($data->training) === false)
        || ($data->additionalInfo != '' && checkAdditionalInfo($data->additionalInfo) === false)
        || ($data->nrPedigree != '' && checkLineage($data->nrPedigree) === false)
        || ($data->ed != '' && checkEdDysplasia($data->ed) === false)
		)
		{
			return false;
		}
		
	return true;
}