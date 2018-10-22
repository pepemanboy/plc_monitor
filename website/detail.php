<?php
// Start the session
session_start();
include_once("user_control.php"); 
$r = validateSession();
if($r == OK)
  include("detail_content.php");
?>