<?php
      
   	include("connect.php");
   	
   	$link=Connection();

      

	$temp1=$_GET["temp1"];
	$hum1=$_GET["hum1"];

	$query = "SELECT (`temperature`, `humidity`)  FROM  `tempLog` 
		VALUES ('".$temp1."','".$hum1."')"; 
   	
   	mysql_query($query,$link);
	mysql_close($link);

   	header("Location: index.php");
      
?>