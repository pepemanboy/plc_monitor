<?php
      
   	include("connect.php");
   	
   	$link=Connection();

      
	$temp1=$_GET["temp1"];
	$hum1=$_GET["hum1"];

	
   $query = "SELECT temperature, humidity  FROM  tempLog ORDER BY timeStamp DESC LIMIT 10";
      


if ($result = mysqli_query($link, $query)) {

    /* fetch associative array */
    while ($row = mysqli_fetch_row($result)) {
        printf ("%s (%s)\n", $row[0], $row[1]);
    }

    /* free result set */
    mysqli_free_result($result);
}
  /* mysqli_query($link, $query);*/
   mysqli_close($link);

   /*	header("Location: index.php");*/
      
?>