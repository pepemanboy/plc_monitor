<?php 
/**
Control devices dropdown
*/

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");
 
// Connect to Database
$link = null;
$r = connectToDatabase($link);
if($r != OK)
{
	mysqli_close($link);
	die();
}

// Get registered plcs
$ids = array();
$names = array();
$r = getPlcList($link, $ids, $names);
if($r != OK)
	_exit("", $link);

for($i = 0; $i < count($ids); $i++)
{
	$name = $names[$i];
	$id = $ids[$i];
	// Echo row
	echo("<a class='dropdown-item dropdown-plc' data-plc-name = '" . $name . "'data-plc-number = '" . $id . "' id = 'control-plc-dropdown-" . $id . "' href='#'>PLC ID: ". $id .". Nombre: " . $name . "</a>");	
}
// Close mySQL server connection
mysqli_close($link);
?>

