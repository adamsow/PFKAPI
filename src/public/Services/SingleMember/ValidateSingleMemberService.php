<?php
require_once __DIR__ . '/../Commons/regexFunctions.php';

function checkMyDetails($data)
{		
	if($data->phone == ''
    || checkMobile($data->phone) === false
    || checkEmail($data->email) === false
    )
    {
        return false;
    }
    
    return true;
}

function checkMyBreedings($data)
{		
    if($data->breedingId == ''
    || ($data->website != ''
    && checkWebsite($data->website) === false)
    )
    {
        return false;
    }
    
    return true;
}