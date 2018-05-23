<?php 
/**
Add plc
*/
// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

//Check for expected POST arguments
if (empty($_POST['plc_name'])) 
{
    _exit(ERROR_ARGUMENTS);
}

// Fetch arguments
$plc_name = $_POST['plc_name']; 

// Connect to Server
$link = null;
$r = connectToDatabase($link);
if($r != OK)
	_exit(ERROR_CONNECTION, $link);

// Add PLC
$r = addPlc($link, $plc_name);
if($r != OK)
	_exit($r, $link);

// Get PLC id
$id = 0;
$r = findPlcByName($link, $plc_name, $id);
if($r != OK)
	_exit($r, $link);

// Return PLC id
echo("plc_id(" . $id . ")");

// Close MySQL connection
_exit(OK, $link); 
?>

