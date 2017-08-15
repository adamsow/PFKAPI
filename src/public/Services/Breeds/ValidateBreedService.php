<?php
require_once __DIR__ . '/../Commons/regexFunctions.php';

function checkBreedData($data)
{
	if($data->breed_pl == '' 
        || $data->breed == ''
        || $data->group == ''
		)
		{
			return false;
		}
		
	return true;
}