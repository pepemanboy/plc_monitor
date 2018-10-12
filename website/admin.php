<?php
// Start the session
session_start();
include_once("user_control.php"); 
validateSession();
include("admin_content.php");
?>