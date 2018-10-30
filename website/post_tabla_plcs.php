<?php 
session_start();

include_once("tabla_plcs.php");

$module = new TablaPlcs();

if (!$module->initialized())
{
	$module = null;
	postDie(ERROR_CONNECTION);
}

$r = $module->postRequest();
$module = null;
?>
