<?php 
/**
Tabla en base de datos: plcX_poweron
*/

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

//Check for expected POST arguments
if (!isset($_POST['operation'])or !isset($_POST['plc_number']) ) 
	_exit(ERROR_ARGUMENTS);

// Fetch arguments
$operation = $_POST['operation'];
$plc_number = $_POST['plc_number'];

// Connect to server and database
$link = null;
$r = connectToDatabase($link);
if ($r != OK)
	_exit($r, $link);

if ($operation == "get")
{
	$r = logPowerOn($link, $plc_number, "get");
	if ($r != OK)
		_exit($r, $link);
}


// Close MySQL connection
_exit(OK, $link); 
?>

