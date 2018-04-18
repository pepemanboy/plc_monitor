<?php
	function Connection(){
		$server="www.cirotec.mx";
		$user="cirotec";
		$pass="Melendez123*";
		$db="plcmonitor_prueba_juanjo";


		$connection = mysql_connect($server, $user, $pass);

		if (!$connection) {
	    	die('MySQL jj  ERROR: ' . mysql_error());
		}
		
		mysql_select_db($db) or die( 'MySQL 2 ERROR: '. mysql_error() );

		return $connection;
	}
?>
