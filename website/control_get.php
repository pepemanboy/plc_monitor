<?php 
/**
Get inputs and outputs
*/

// Includes
include_once("definitions.php");
include_once("connect.php");

// Check for expected POST arguments
if (empty($_POST['plc_number'])) 
{
    _exit(ERROR_ARGUMENTS);
}

// Fetch arguments
$suffix = "plc" . $_POST['plc_number'] . "_";

//Tables to query  
$inputs_table = $suffix . "inputs";
$outputs_table = $suffix . "outputs";

//Connect to server and database
$link = null;
$r = connectToDatabase($link);
if($r != OK)
	_exit($r, $link);

// Inputs

// Query table existent
$exists = False;
$r = tableExists($link, $inputs_table, $exists); 
if($r != OK)
	_exit($r, $link);

// Create table if it doesnt exist
if (!$exists)
{
     $query = "
     CREATE TABLE " . $inputs_table . " (
     timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
     	di1 int(11) NOT NULL,
		di2 int(11) NOT NULL,
		di3 int(11) NOT NULL,
		di4 int(11) NOT NULL,
		di5 int(11) NOT NULL,
		di6 int(11) NOT NULL,
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
}

// Query table empty
$empty = False;
$r = tableEmpty($link, $table_name, $empty);
if($r != OK)
	_exit($r, $link);

// Insert one row if it doesnt exist
if ($empty)
{
	$query = "
	INSERT INTO " . $table_name . " 
	(di1,di2,di3,di4,di5,di6,ai1,ai2,ai3,ai4,ai5,ai6) 
	VALUES (0,0,0,0,0,0,0,0,0,0,0,0);
	";  	

	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

// Query digital Inputs
$query = "SELECT /*+ MAX_EXECUTION_TIME(1000) */ di1,di2,di3,di4,di5,di6,ai1,ai2,ai3,ai4,ai5,ai6 FROM  " . $table_name . " ORDER BY timeStamp DESC LIMIT 1"; 
if ($result = mysqli_query($link, $query)) 
{
	// Get row
    $row = mysqli_fetch_row($result);   

	// Output digital_inputs variable
    echo("digital_inputs("); 
    for($i = 0; $i < 6; $i++)
    {
    	echo($row[$i]);
    	if ($i != 5) echo (",");
    }
    echo(")");

    // Output analog_inputs variable
    echo("analog_inputs(");  
    for($i = 6; $i < 12; $i++)
    {
    	echo($row[$i]);
    	if ($i != 11) echo (",");
    }
    echo(")");

    // Free result
    mysqli_free_result($result);
}
else
	_exit(ERROR_QUERY, $link);

// Close connection
_exit(OK,$link); 

?>

