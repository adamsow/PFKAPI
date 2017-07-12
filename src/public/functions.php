<?php
function hello($db, $log)
{
	try
	{
		$log -> addInfo("Something interesting happened");
		/*foreach($db->query('SELECT * from panstwo') as $row) 
		{
			print_r($row);
		}*/
	}
	catch(Exception $e)
	{
		echo ($e->getMessage());
		$log -> addError($e->getMessage());
	}
}

function GetToken()
{
	echo "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhZG1pbiI6InRydWUifQ.oo_PqRUuzA5k6BhnaAyJR0lZdun6oIEm5FIzUOwHO88";
}
