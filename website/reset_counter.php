<?php 
/**
Reset counters
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
$table_name = $suffix . "reset";
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
     	r1 int(11) NOT NULL,
		r2 int(11) NOT NULL,
		r3 int(11) NOT NULL,
		r4 int(11) NOT NULL,
		r5 int(11) NOT NULL,
		r6 int(11) NOT NULL
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
	(r1,r2,r3,r4,r5,r6) 
	VALUES (-1,-1,-1,-1,-1,-1);
	";  	

	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

if($operation == "get")
{	
	// Query inputs
	$query = "SELECT /*+ MAX_EXECUTION_TIME(1000) */ r1,r2,r3,r4,r5,r6 FROM  " . $table_name . " ORDER BY timeStamp DESC LIMIT 1"; 
	if ($result = mysqli_query($link, $query)) 
	{
		// Get row
	    $row = mysqli_fetch_row($result);   

		// Output digital_inputs variable
	    echo("resets("); 
	    for($i = 0; $i < 6; $i++)
	    {
	    	echo($row[$i]);
	    	if ($i != 5) echo (",");
	    }
	    echo(")");

	    // Free result
	    mysqli_free_result($result);
	}
	else
		_exit(ERROR_QUERY, $link);

	// Delete row
	$query = "DELETE FROM " . $table_name;
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}
else if ($operation == "set")
{
	echo("{");
	if (!isset($_POST['r1']) or !isset($_POST['r2']) or !isset($_POST['r3']) or !isset($_POST['r4']) or !isset($_POST['r5']) or !isset($_POST['r6'])) 
		_exit(ERROR_ARGUMENTS);
	$r1 = $_POST['r1'];
	$r2 = $_POST['r2'];
	$r3 = $_POST['r3'];
	$r4 = $_POST['r4'];
	$r5 = $_POST['r5'];
	$r6 = $_POST['r6'];

	// Delete
	$query = "DELETE FROM " . $table_name;
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);

	// Insert
	$query = "INSERT INTO " . $table_name . " (r1,r2,r3,r4,r5,r6) VALUES (" . $r1 . "," . $r2 . "," . $r3 . "," . $r4 . "," . $r5 . "," . $r6 . ")";
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

// Close connection
_exit(OK,$link); 
?>

