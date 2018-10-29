<?php
// Start the session
session_start();
include_once("user_control.php"); 
validateSession();

// Get page title
include_once("customize.php");
$customize = new Customize();
$title = "PLC_MONITOR";
if ($customize->initialized())
  $r = $customize->getTitle($title);
$customize = null;

 include("actions_content.php");
?>