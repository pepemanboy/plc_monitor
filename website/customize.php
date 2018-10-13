<?php 
session_start();

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

// Constants
$TABLE_NAME = "plc_customize";
$DEFAULT_TITLE = "SCADA";

/**
*	Create customize table if it doesnt exist. 
*	If it exists and is empty, add default row
*	@param link mysql connection
*	@return error code
*/
function createCustomizeTable(&$link)
{
	$table_name = $GLOBALS['TABLE_NAME'];
	$default_title = $GLOBALS['DEFAULT_TITLE'];

	// Assert connection
	if (!$link)
		return ERROR_CONNECTION;

	// Query table existent
	$exists = False;
	$r = tableExists($link, $table_name, $exists); 
	if($r != OK)
		return $r;

	// Create table if it doesnt exist
	if (!$exists)
	{
	     $query = "
	     CREATE TABLE " . $table_name . " (
	        title VARCHAR(60) NOT NULL
		)
		";
		$r = mysqli_query($link,$query);
		if (!$r)
			return ERROR_QUERY;
	}

	// Query table empty
	$empty = False;
	$r = tableEmpty($link, $table_name, $empty);
	if($r != OK)
		return $r;

	// Insert one row if it doesnt exist
	if ($empty)
	{
		$query = "INSERT INTO " . $table_name . "(title) VALUES (" . $default_title . ")";
		$r = mysqli_query($link, $query);
		if (!$r)
			return ERROR_QUERY;
	}

	return OK;
}

function getProperty($property)
{
	
}



?>
