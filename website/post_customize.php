<?php 
session_start();

include_once("customize.php");

$module = new Customize();

if (!$module->initialized())
{
	$module = null;
	postDie(ERROR_CONNECTION);
}

$r = $module->postRequest();
$module = null;
?>

