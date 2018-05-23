<?php 
/**
Control Digital Inputs
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
$r = mysqli_query($link, "Select 1 from `digital_inputs` LIMIT 1;"); // False if table not present
if(!$r)
{
     $query = "
     CREATE TABLE digital_inputs (
     timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
     	di1 int(11) NOT NULL,
		di2 int(11) NOT NULL,
		di3 int(11) NOT NULL,
		di4 int(11) NOT NULL,
		di5 int(11) NOT NULL,
		di6 int(11) NOT NULL	
	)
	";
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);

	$query = "
	INSERT INTO digital_inputs 
	(di1,di2,di3,di4,di5,di6) 
	VALUES (0,0,0,0,0,0);
	";  	

	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

/** Retrieve digital inputs */

$query = "SELECT di1,di2,di3,di4,di5,di6 FROM  digital_inputs ORDER BY timeStamp DESC LIMIT 1";
      
$arr = [];
 
if ($result = mysqli_query($link, $query)) 
{
	// Output digital_inputs variable
    echo("digital_inputs(");
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

