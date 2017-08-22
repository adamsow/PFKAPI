<?php
require_once __DIR__ . '/../Commons/regexFunctions.php';
function checkLitterData($data)
{
	if($data->quantity == '' 
		|| $data->bitchQuantity == ''
		|| checkIfDateInPastOrToday($data->birthDate) === false
		|| ($data->copulationDate != '' && checkIfDateInPastOrToday($data->birthDate) === false)
		|| $data->mother == ''
		|| checkFullname($data->mother) === false
		|| $data->father == ''
		|| checkFullname($data->father) === false
		|| $data->breed == ''
		|| $data->nickname == ''
		|| $data->accepted == ''
		|| ($data->additionalInfo != '' && checkAdditionalInfo($data->additionalInfo) === false)
		)
		{
			return false;
		}
		
	return true;
}