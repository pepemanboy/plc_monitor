<?php 
session_start();

include_once("customize.php");

$customize = new Customize();

if (!$customize->initialized())
{
	$customize = null;
	postDie(ERROR_CONNECTION);
}

$r = $customize->postRequest();
$customize = null;
?>
