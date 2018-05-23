<?php 
/**
Control analog inputs
*/

/* Includes */
include_once("definitions.php");
include_once("connect.php");

/* Connect to server and database */
$link = null;
$r = connectToDatabase($link, "plcmonitor_prueba_juanjo");
if($r != OK)
	_exit($r, $link);

/** Create table if not present */
$r = mysqli_query($link, "Select 1 from `analog_inputs` LIMIT 1;"); // False if table not present
if(!$r)
{
     $query = "
     CREATE TABLE analog_inputs (
     timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
     	ai1 int(11) NOT NULL,
		ai2 int(11) NOT NULL,
		ai3 int(11) NOT NULL,
		ai4 int(11) NOT NULL,
		ai5 int(11) NOT NULL,
		ai6 int(11) NOT NULL	
	)
	";
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);

	$query = "
	INSERT INTO analog_inputs 
	(ai1,ai2,ai3,ai4,ai5,ai6) 
	VALUES (0,0,0,0,0,0);
	";  	

	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

/** Retrieve digital inputs */

$query = "SELECT ai1,ai2,ai3,ai4,ai5,ai6 FROM  analog_inputs ORDER BY timeStamp DESC LIMIT 1";
      
$arr = [];
 
if ($result = mysqli_query($link, $query)) 
{
	// Output digital_inputs variable
    echo("analog_inputs(");
    while ($row = mysqli_fetch_row($result)) 
    {
        for($i = 0; $i < 6; $i++)
        {
        	echo($row[$i]);
        	if ($i != 5) echo (",");
        }
    }
    echo(")");
    mysqli_free_result($result);
}else
{
	_exit(ERROR_QUERY, $link);
}

/** Close mySQL server connection */
_exit(OK,$link); 

?>

