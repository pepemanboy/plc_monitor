<?php 
/**
Viz get signal names
*/
// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

//Check for expected POST arguments
$txt = $_POST['txt'];
// echo("Received = " . $txt);
echo("{El texto es = " . $txt . "}");

?>