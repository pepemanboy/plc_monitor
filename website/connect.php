<?php

	function Connection(){
		$server="localhost";
		$user="pepemanboy";
		$pass="123456";
		$db="plcmonitor_prueba_juanjo";

		echo "Intento ";


		$connection = mysqli_connect($server, $user, $pass, $db);
		
	 if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
        }
        echo "Connected successfully";

		return $connection;
	}
?>

