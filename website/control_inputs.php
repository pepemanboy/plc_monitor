<?php 
/**
Control Inputs
*/

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

// Check for expected POST arguments
if (empty($_POST['plc_number']) or empty($_POST['operation'])) 
{
    _exit(ERROR_ARGUMENTS);
}

// Fetch arguments
$plc_number = $_POST['plc_number'];
$suffix = "plc" . $_POST['plc_number'] . "_";  
$table_name = $suffix . "inputs";
$operation = $_POST['operation'];

//Connect to server and database
$link = null;
$r = connectToDatabase($link);
if($r != OK)
	_exit($r, $link);

// Check if plc exists
$name = "";
$r = findPlcById($link,$plc_number,$name);
if ($r != OK)
	_exit($r, $link);

// Query table existent
$exists = False;
$r = tableExists($link, $table_name, $exists); 
if($r != OK)
	_exit($r, $link);

// Create table if it doesnt exist
if (!$exists)
{
     $query = "
     CREATE TABLE " . $table_name . " (
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

if($operation == "get")
{	
	// Query inputs
	$query = "SELECT /*+ MAX_EXECUTION_TIME(1000) */ di1,di2,di3,di4,di5,di6,ai1,ai2,ai3,ai4,ai5,ai6 FROM  " . $table_name . " ORDER BY timeStamp DESC LIMIT 1"; 
	if ($result = mysqli_query($link, $query)) 
	{
		// Get row
	    $row = mysqli_fetch_row($result);   

	    if(!isset($_POST['arduino']))
	    {
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
	    }
	    else // Arduino
	    {
	    	echo("{");
	    	echo("di(");
	    	for($i = 0; $i < 6; $i++)
		    {
		    	echo($row[$i]);
		    	if ($i != 5) echo (",");
		    }
		    echo(")");
	    }
	    // Free result
	    mysqli_free_result($result);
	}
	else
		_exit(ERROR_QUERY, $link);
}
else if ($operation == "set")
{
	echo("{");
	if (!isset($_POST['di1']) or !isset($_POST['di2']) or !isset($_POST['di3']) or !isset($_POST['di4']) or !isset($_POST['di5']) or !isset($_POST['di6']) or !isset($_POST['ai1']) or !isset($_POST['ai2']) or !isset($_POST['ai3']) or !isset($_POST['ai4']) or !isset($_POST['ai5']) or !isset($_POST['ai6'])) 
		_exit(ERROR_ARGUMENTS);
	$di1 = $_POST['di1'];
	$di2 = $_POST['di2'];
	$di3 = $_POST['di3'];
	$di4 = $_POST['di4'];
	$di5 = $_POST['di5'];
	$di6 = $_POST['di6'];
	$ai1 = $_POST['ai1'];
	$ai2 = $_POST['ai2'];
	$ai3 = $_POST['ai3'];
	$ai4 = $_POST['ai4'];
	$ai5 = $_POST['ai5'];
	$ai6 = $_POST['ai6'];

	// Delete
	$query = "DELETE FROM " . $table_name;
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);

	// Insert
	$query = "INSERT INTO " . $table_name . " (di1,di2,di3,di4,di5,di6,ai1,ai2,ai3,ai4,ai5,ai6) VALUES (" . $di1 . "," . $di2 . "," . $di3 . "," . $di4 . "," . $di5 . "," . $di6 . "," . $ai1 . "," . $ai2 . "," . $ai3 . "," . $ai4 . "," . $ai5 . "," . $ai6 . ")";
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

// Close connection
_exit(OK,$link); 

?>

