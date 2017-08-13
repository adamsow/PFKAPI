<?php
require_once __DIR__ . '/../Commons/regexFunctions.php';

function checkColorData($data)
{
	if($data->color_pl == '' 
        || $data->color == ''
		)
		{
			return false;
		}
		
	return true;
}