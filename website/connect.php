<?php

	function Connection(){
		$server="localhost";
		$user="pepemanboy";
		$pass="123456";
		$db="plcmonitor_prueba_juanjo";

		$connection = mysql_connect($server, $user, $pass);

		if (!$connection) {
	    	die('MySQL ERROR 1 : ' . mysql_error());
		}
		
		mysql_select_db($db) or die( 'MySQL ERROR 2 : '. mysql_error() );

		return $connection;
	}
?>

