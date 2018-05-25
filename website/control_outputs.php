<?php 
/**
Control outputs
*/
// Includes
include_once("definitions.php");
include_once("connect.php");

//Check for expected POST arguments
if (empty($_POST['operation']) or empty($_POST['plc_number']))
{
	_exit(ERROR_ARGUMENTS, $link);
}

$operation = $_POST['operation'];
$plc_number = $_POST['plc_number'];
$suffix = "plc" . $plc_number . "_";  
$table_name = $suffix . "outputs";

// Connect to server and database
$link = null;
$r = connectToDatabase($link);
if ($r != OK)
	_exit($r, $link);

// Query table existent
$exists = False;
$r = tableExists($link, $table_name, $exists); 
if ($r != OK)
	_exit($r, $link);

// Create table if it doesnt exist
if (!$exists)
{
     $query = "
     CREATE TABLE " . $table_name . " (
     timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
     	do1 int(11) NOT NULL,
		do2 int(11) NOT NULL,
		do3 int(11) NOT NULL,
		do4 int(11) NOT NULL,
		do5 int(11) NOT NULL,
		do6 int(11) NOT NULL	
	)
	";
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

// Query table empty
$empty = True;
$r = tableEmpty($link, $table_name, $empty); 
if (!$r)
	_exit(ERROR_QUERY, $link);
if($empty)
{
	// Insert first row
	$query = "
	INSERT INTO " . $table_name . " 
	(do1,do2,do3,do4,do5,do6) 
	VALUES (0,0,0,0,0,0);
	";  
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

// Set outputs
if ($operation == "set")
{
	if (empty($_POST['outputs']) ) 
	    _exit(ERROR_ARGUMENTS, $link);

	// Fetch arguments
	$arr = $_POST['outputs'];  	

	// Post control outputs to table
	$query = "
	DELETE FROM " . $table_name . ";
	INSERT INTO " . $table_name . " 
	(do1,do2,do3,do4,do5,do6) 
	VALUES (".$arr[0].",".$arr[1].",".$arr[2].",".$arr[3].",".$arr[4].",".$arr[5].");
	";  	

	$r = mysqli_multi_query($link, $query);
	if (!$r)
		_exit(ERROR_QUERY, $link);

	do{} while(mysqli_more_results($link) && mysqli_next_result($link)); // flush multi queries
}
// Get outputs
else
{
	// Query outputs
	$query = "SELECT /*+ MAX_EXECUTION_TIME(1000) */ do1,do2,do3,do4,do5,do6  FROM  " . $table_name . " ORDER BY timeStamp DESC LIMIT 1"; 
	if ($result = mysqli_query($link, $query)) 
	{
		// Get row
	    $row = mysqli_fetch_row($result);   

		// Output digital_outputs variable
	    echo("digital_outputs("); 
	    for($i = 0; $i < 6; $i++)
	    {
	    	echo($row[$i]);
	    	if ($i != 5) echo (",");
	    }
	    echo(")");

	    // Free result
	    mysqli_free_result($result);
	}else
	{
		_exit(ERROR_QUERY,$link);
	}
}
// Close MySQL connection
_exit(OK, $link); 
?>

