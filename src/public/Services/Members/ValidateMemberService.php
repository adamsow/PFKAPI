<?php
require_once __DIR__ . '/../Commons/regexFunctions.php';

function checkMemberData($data)
{
	if($data->name == '' 
		|| checkName($data->name)===false
		|| $data->status == ''
		|| $data->country == ''
		|| $data->accepted == ''
		|| $data->birthDate == ''
		|| $data->fee == ''
		|| checkIfDateInPastOrToday($data->birthDate) === false
		|| $data->surname == ''
		|| checkFullname($data->surname) === false
		|| $data->street == ''
		|| checkStreet($data->street) === false
		|| $data->postal == ''
		|| checkPostal($data->postal) === false
		|| $data->city == ''
		|| checkCity($data->city) === false
		|| $data->voivodeship == ''
		|| checkCity($data->voivodeship) === false
		|| ($data->phone != '' && checkMobile($data->phone) === false)
		|| ($data->mobile != '' && checkMobile($data->mobile) === false)
		|| ($data->characteristic != '' && checkAdditionalInfo($data->characteristic) === false)
		|| ($data->functions != '' && checkAdditionalInfo($data->functions) === false)
		|| ($data->additionalInfo != '' && checkAdditionalInfo($data->additionalInfo) === false)
		|| ($data->removeDate != '' && checkIfValidDate($data->removeDate) === false)
		|| checkEmail($data->email) === false
		)
		{
			return false;
		}
		
	return true;
}