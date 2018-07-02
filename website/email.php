<?php

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");


// TODO get email from db
$email = "pepe_ciro@hotmail.com";

$message = "Jejejeje ";
$subject = "Email de prueba";
$header = "From: pepe@pepe.com";

// send email
mail($email,$subject,$message,$header);

// Close MySQL connection
_exit(OK, $link); 
?>