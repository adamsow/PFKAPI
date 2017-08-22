<?php
require_once __DIR__ . '/../Commons/regexFunctions.php';

function checkBreedingData($data)
{
	if($data->nickname == '' 
        || checkFullname($data->nickname)===false
        || $data->written == ''
		|| $data->unregisterDate != '' && checkIfValidDate($data->unregisterDate) === false
		|| count($data->breeds) < 1
		|| $data->address != '' && checkAddress($data->address) === false
		|| $data->email != '' && checkEmail($data->email) === false
		|| $data->website != '' && checkWebsite($data->website) === false
		|| $data->characteristic != '' && checkAdditionalInfo($data->characteristic) === false
		|| $data->additionalInfo != '' && checkAdditionalInfo($data->additionalInfo) === false
		|| $data->accepted == ''
		|| count($data->owners) < 1
		)
		{
			return false;
		}
		
	return true;
}