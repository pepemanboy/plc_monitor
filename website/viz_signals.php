<?php 
/**
Viz get signal names
*/
// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

//Check for expected POST arguments
if (!isset($_POST['plc_number']))
	_exit(ERROR_ARGUMENTS, $link);