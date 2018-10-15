<?php
// Start the session
session_start();
include_once("user_control.php"); 
$r = validateSession();
if($r == OK)
  if(adminSession())
  {
    include("options_content.php");
  } 
  else
  {
    echo("<meta http-equiv='refresh' content='0; url=admin.php' />");
  }
?>