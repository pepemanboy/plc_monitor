<?php
	function Connection(){
		$server="http://cirotec.mx";
		$user="pepemanboy";
		$pass="pepe1995*";
		$db="database";
	   	
		$connection = mysql_connect($server, $user, $pass);

		if (!$connection) {
	    	die('MySQL ERROR: ' . mysql_error());
		}
		
		mysql_select_db($db) or die( 'MySQL ERROR: '. mysql_error() );

		return $connection;
	}
?>
