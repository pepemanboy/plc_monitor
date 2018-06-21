<?php
if(!defined("DEFINITIONS"))
{
	/* Definiitions module */
	define("DEFINITIONS", "DEFINITIONS");

	/* Username and password */
	define("SERVER", "localhost");
	define("USERNAME", "pepemanb_pepeman");
	define("PASSWORD", "pepe1995*");
	define("DATABASE", "pepemanb_plcmonitor");
	define("TABLE_PREFIX", "plc");
	define("MAIN_TABLE", "plcs");

	/* Errors */
	define("OK", "OK");
	define("ERROR_CONNECTION", "CONNECTION ERROR");
	define("ERROR_QUERY", "QUERY ERROR");
	define("ERROR_ARGUMENTS", "ARGUMENTS ERROR");
	define("ERROR_SELECT_DB", "SELECTDB ERROR");

	/* Input types */
	define("TYPE_DIGITAL", 0);
	define("TYPE_COUNTER", 1);
	define("TYPE_ANALOG", 2);

	/* Action types */
	define("ACTION_NONE", 0);
	define("ACTION_PERMANENT", 1);
	define("ACTION_EVENT", 2);
	define("ACTION_DELAY", 3);

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
		die("error(" . $str . ")}");
	}
} // End definitions module

?>