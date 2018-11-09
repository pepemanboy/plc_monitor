<?php
session_start();

// Validate session
include_once("user_control_oo.php"); 
// UserControl::validateSession(); // Will redirect if fails

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