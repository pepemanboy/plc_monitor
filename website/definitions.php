<?php
if(!defined("DEFINITIONS"))
{
	/* Definiitions module */
	define("DEFINITIONS", "DEFINITIONS");

	/* Username and password */
	define("SERVER", "localhost");
	define("USERNAME", "pepemanb_test");
	define("PASSWORD", "VO+%A;WqeHWA");
	define("DATABASE", "pepemanb_plcmonitor");
	define("TABLE_PREFIX", "plc");
	define("MAIN_TABLE", "plcs");

	/* Errors */
	define("OK", "OK");
	define("ERROR_CONNECTION", "CONNECTION ERROR");
	define("ERROR_QUERY", "QUERY ERROR");
	define("ERROR_ARGUMENTS", "ARGUMENTS ERROR");
	define("ERROR_SELECT_DB", "SELECTDB ERROR");

	/* Debugging */
	define("DEBUGGING", "DEBUGGING");

	function _debug($str)
	{
		if(defined("DEBUGGING"))
			echo($str);
	}

	/* Exit */
	function _exit($str, &$connection = null)
	{
		if($connection)
			mysqli_close($connection);
		die("error(" . $str . ")");
	}
} // End definitions module

?>