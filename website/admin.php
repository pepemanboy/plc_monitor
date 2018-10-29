<?php
session_start();

include_once("user_control.php"); 
validateSession(); // Will redirect if fails

// Get page title
include_once("customize.php");
$customize = new Customize();
$title = "PLC_MONITOR";
if ($customize->initialized())
 	$r = $customize->getTitle($title);
$customize = null;

// Load html
include("admin_content.php");
?>