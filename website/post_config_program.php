<?php 
session_start();

include_once("config_program_oo.php");

$module = new Config();

if (!$module->initialized())
{
	$module = null;
	postDie(ERROR_CONNECTION);
}

$r = $module->postRequest();
$module = null;
?>

