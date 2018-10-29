<?php
// Start the session
session_start();
include_once("user_control.php"); 
$r = validateSession();
if($r == OK)
  if(adminSession())
  {
  	// Get page title
	include_once("customize.php");
	$customize = new Customize();
	$title = "PLC_MONITOR";
	if ($customize->initialized())
	  $r = $customize->getTitle($title);
	$customize = null;
	
    include("manager_content.php");
  } 
  else
  {
    echo("<meta http-equiv='refresh' content='0; url=admin.php' />");
  }
?>