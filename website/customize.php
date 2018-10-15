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
		$query = "INSERT INTO " . $table_name . "(title) VALUES ('" . $default_title . "')";
		$r = mysqli_query($link, $query);
		if (!$r)
			return ERROR_QUERY;
	}

	return OK;
}

/** 
*	Get property from database table.
*/
function getProperty(&$link, $property, &$value)
{
	$table_name = $GLOBALS['TABLE_NAME'];

	// Query table
	$query = "
	SELECT " . $property . " FROM " . $table_name . " LIMIT 1";

	$result = mysqli_query($link, $query);
	if (!$result)
		return ERROR_QUERY;

	// Compare username and password
	if (($n = mysqli_num_rows($result)) > 0) 
	{
		$row = mysqli_fetch_assoc($result);
		$value = $row[$property];		
		mysqli_free_result($result);
	}

	return OK;
}

/**
*	Set property in table.
*/
function setProperty(&$link, $property, $value)
{
	$table_name = $GLOBALS['TABLE_NAME'];

	$query = "
	UPDATE " . $table_name . "
	SET " . $property . " = '" . $value . "';
	";

	$r = mysqli_query($link, $query);
	if (!$r)
		return ERROR_QUERY . "cc";

	return OK;
}

function customizeGetProperties(&$link, &$message)
{
	// Get title
	$title = "";
	$r = getProperty($link, "title", $title);
	if ($r != OK)
		return $r;
	$message .= "title(" . $title . ")";

	return OK;
}

function customizeSetProperties(&$link, &$message)
{
	// Title
	if(!isset($_POST["property_title"]))
		return ERROR_ARGUMENTS;
	$title = $_POST["property_title"];
	$r = setProperty($link, "title", $title);
	if ($r != OK)
		return $r;

	return OK;
}

function customizePostRequest()
{
	if ($_SERVER["REQUEST_METHOD"] != "POST")
		return;

	if(!isset($_POST["operation"]))
		return;

	$operation = $_POST["operation"];

	// Connect to server and database
	$link = null;
	$r = connectToDatabase($link);
	if ($r != OK)
		_exit($r, $link);

	// Assert table
	$r = createCustomizeTable($link);
	if($r != OK)
		_exit($r, $link);

	$message = "";
	switch ($operation) 
	{
	    case "get_properties": $r = customizeGetProperties($link, $message); break;
	    case "set_properties": $r = customizeSetProperties($link, $message); break;
	    default: break;
    }

    if ($r != OK)
    	_exit($r, $link);

    echo($message);

    _exit(OK, $link);	
}

function getTitle(&$title)
{
	// Connect to server and database
	$link = null;
	$r = connectToDatabase($link);
	if ($r != OK)
		return __exit($r, $link);

	// Assert table
	$r = createCustomizeTable($link);
	if($r != OK)
		return __exit($r, $link);

	$r = getProperty($link, "title" , $title);

	return __exit($r, $link);
}

?>
