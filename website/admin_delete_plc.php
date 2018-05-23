<?php 
/**
Control Inputs
*/

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");


// Check for expected POST arguments
if (empty($_POST['plc_number'])) 
{
    _exit(ERROR_ARGUMENTS);
}

//Connect to server and database
$link = null;
$r = connectToDatabase($link);
if($r != OK)
	_exit($r, $link);

// Fetch arguments
$plc_number = $_POST['plc_number'];
$suffix = "plc" . $plc_number . "_";  
$table_inputs = $suffix . "inputs";
$table_outputs = $suffix . "outputs";
$table_config = $suffix . "config";
$tables = array($table_config, $table_inputs, $table_outputs);

// Delete registry from main table
$r = deletePlc($link, $plc_number);
if($r != OK)
	_exit($r, $link);

// Delete tables if they exist
foreach ($tables as $table)
{
	// Query table existent
	$exists = False;
	$r = tableExists($link, $table, $exists); 
	if($r != OK)
		_exit($r, $link);

	if(!$exists)
		continue;

	// Drop table
	$r = mysqli_query($link, "DROP TABLE " . $table);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

// Close connection
_exit(OK,$link); 

?>

