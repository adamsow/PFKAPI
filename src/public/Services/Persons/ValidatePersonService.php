<?php
require_once __DIR__ . '/../Commons/regexFunctions.php';

function checkPersonData($data)
{
	if($data->name == '' 
		|| checkName($data->name)===false
		|| $data->country == ''
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
		|| checkEmail($data->email) === false
		)
		{
			return false;
		}
		
	return true;
}