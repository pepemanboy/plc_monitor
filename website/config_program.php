<?php 
/**
Configuration program set / get
*/
// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

//Check for expected POST arguments
if (empty($_POST['operation']) or empty($_POST['plc_number']))
{
	_exit(ERROR_ARGUMENTS, $link);
}

$operation = $_POST['operation'];
$plc_number = $_POST['plc_number'];
$suffix = "plc" . $plc_number . "_";  
$table_name = $suffix . "config";

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
	timeStamp TIMESTAMP NOT NULL PRIMARY KEY";
	for($i = 1; $i <= 6; $i ++)
	{
		$query = $query . "
		, di" . $i . "_name VARCHAR(200) NOT NULL,
		di" . $i . "_freq int(11) NOT NULL,
		di" . $i . "_count BIT NOT NULL,

		ai" . $i . "_name VARCHAR(200) NOT NULL,
		ai" . $i . "_freq int(11) NOT NULL,
		ai" . $i . "_gain FLOAT(5,2) NOT NULL,
		ai" . $i . "_offs FLOAT(5,2) NOT NULL,

		do" . $i . "_name VARCHAR(200) NOT NULL
		";
	}
	$query = $query . ")";
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
	echo("Empty");
	// Insert first row
	$query = "INSERT INTO " . $table_name . "(";
	for($i = 1; $i <= 6; $i ++)
	{
		$query = $query . "
		di" . $i . "_name,
		di" . $i . "_freq,
		di" . $i . "_count,

		ai" . $i . "_name,
		ai" . $i . "_freq,
		ai" . $i . "_gain,
		ai" . $i . "_offs,

		do" . $i . "_name";
		if ($i != 6) 
			$query = $query . ",";
	} 
	$query = $query . ")";

	// Values
	$query = $query . " VALUES(";
	for($i = 1; $i <= 6; $i ++)
	{
		$query = $query . "
		'Digital Input " . $i . "',
		0,
		0,

		'Analog Input " . $i . "',
		0,
		1,
		0,

		'Digital Output " . $i . "'";
		if ($i != 6) 
			$query = $query . ",";
	} 
	$query = $query . ");";
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

// Set outputs
if ($operation == "set")
{
	if (empty($_POST['di']) || empty($_POST['ai']) || empty($_POST['dout']) ) 
		_exit(ERROR_ARGUMENTS, $link);

	// Fetch arguments
	$digital_inputs = $_POST['di'];  
	$analog_inputs = $_POST['ai'];  
	$digital_outputs = $_POST['dout'];  

	$query = "
	DELETE FROM " . $table_name . ";
	INSERT INTO " . $table_name . " (";
	for($i = 1; $i <= 6; $i ++)
	{
		$query = $query . "
		di" . $i . "_name,
		di" . $i . "_freq,
		di" . $i . "_count,

		ai" . $i . "_name,
		ai" . $i . "_freq,
		ai" . $i . "_gain,
		ai" . $i . "_offs,

		do" . $i . "_name";
		if ($i != 6) 
			$query = $query . ",";
	} 
	$query = $query . ") ";
	// Values
	$query = $query . "VALUES(";
	for($i = 0; $i < 6; $i ++)
	{		
		$di = $digital_inputs[$i];
		$ai = $analog_inputs[$i];
		$do = $digital_outputs[$i];

		$query = $query . "
		'" . $di[0] . "',
		" . $di[1] . ",
		" . $di[2] . ",

		'" . $ai[0] . "',
		" . $ai[1] . ",
		" . $ai[2] . ",
		" . $ai[3] . ",

		'" . $do[0] . "'";

		if ($i != 5) 
			$query = $query . ",";
	} 
	$query = $query . ");";
	$r = mysqli_multi_query($link, $query);
	if (!$r)
		_exit(ERROR_QUERY, $link);

	do{} while(mysqli_more_results($link) && mysqli_next_result($link)); // flush multi queries
}
// Get config
else if ($operation == "get")
{
	echo("{");
	// Query inputs
	$query = "SELECT ";
	for($i = 1; $i <= 6; $i ++)
	{
		$query = $query . "
		di" . $i . "_name,
		di" . $i . "_freq,
		di" . $i . "_count,

		ai" . $i . "_name,
		ai" . $i . "_freq,
		ai" . $i . "_gain,
		ai" . $i . "_offs,

		do" . $i . "_name";
		if ($i != 6) 
			$query = $query . ",";
	} 
	// From
	$query = $query . " FROM " . $table_name . " ORDER BY timeStamp DESC LIMIT 1";

	if ($result = mysqli_query($link, $query)) 
	{
		// Get row
		$row = mysqli_fetch_assoc($result);  
		if (isset($_POST['arduino']))
		{
			for ($i = 1; $i <= 6; $i ++)
			{
				echo("di" . $i . "(" . $row["di" . $i . "_freq"] . "," . $row["di" . $i . "_count"] . ")");
				echo("ai" . $i . "(" . $row["ai" . $i . "_freq"] . "," . $row["ai" . $i . "_gain"] . "," . $row["ai" . $i . "_offs"] . ")"); 
			}
		} 
		else
		{
			for ($i = 1; $i <= 6; $i ++)
			{
				echo("di" . $i . "(" . $row["di" . $i . "_name"] . "," . $row["di" . $i . "_freq"] . "," . $row["di" . $i . "_count"] . ")");
				echo("ai" . $i . "(" . $row["ai" . $i . "_name"] . "," . $row["ai" . $i . "_freq"] . "," . $row["ai" . $i . "_gain"] . "," . $row["ai" . $i . "_offs"] . ")"); 
				echo("do" . $i . "(" . $row["do" . $i . "_name"] .  ")");
			}
		}		
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

