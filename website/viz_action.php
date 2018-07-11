<?php 
/**
Visualization action
*/

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

//Check for expected POST arguments
if (!isset($_POST['plc_number']) or !isset($_POST['operation']))
{
  _exit(ERROR_ARGUMENTS, $link);
}

$plc_number = $_POST['plc_number'];
$suffix = "plc" . $plc_number . "_";  
$table_name = $suffix . "actions";
$operation = $_POST['operation'];

// Checksum
$cs = "";

// Connect to server and database
$link = null;
$r = connectToDatabase($link);
if ($r != OK)
  _exit($r, $link);

// Check if plc exists
$name = "";
$r = findPlcById($link,$plc_number,$name);
if ($r != OK)
  _exit($r, $link);

// Query table existent
$exists = False;
$r = tableExists($link, $table_name, $exists); 
if ($r != OK)
  _exit($r, $link);

// Create table if it doesnt exist
if (!$exists)
{
  $query = "
  CREATE TABLE " . $table_name . " (
  id int NOT NULL AUTO_INCREMENT,
  input VARCHAR(10) NOT NULL,
  threshold float(5,2) NOT NULL,
  updown BIT NOT NULL,
  output int(11) NOT NULL,
  email VARCHAR(200) NOT NULL,
  notification_interval_s int(11) NOT NULL,
  action_type int(11) NOT NULL,
  delay_s int(11) NOT NULL,
  PRIMARY KEY (id))";
  $r = mysqli_query($link,$query);
  if (!$r)
    _exit(ERROR_QUERY, $link);
}

if ($operation == "add")
{
  // Check for arguments
  if(!isset($_POST['input']) or !isset($_POST['threshold']) or !isset($_POST['updown']) or !isset($_POST['output']) or !isset($_POST['email']) or !isset($_POST['notification_interval_s']) or !isset($_POST['action_type']) or !isset($_POST['delay_s']))
  {
    _exit(ERROR_ARGUMENTS, $link);
  }

  // Fetch arguments
  $input = $_POST['input'];
  $threshold = $_POST['threshold'];
  $updown = $_POST['updown'];
  $output = $_POST['output'];
  $email = $_POST['email'];
  $notification_interval_s = $_POST['notification_interval_s'];
  $action_type = $_POST['action_type'];
  $delay_s = $_POST['delay_s'];
/*
  echo("Table " . $table_name . " Output " . $output .  " Input " . $input . " threshold " . $threshold . " updown " . $updown . " output " . $output . " email " . $email . " notification " . $notification_interval_s . " action " . $action_type . " delay " . $delay_s);*/

  if($output > 0)
  {
    // Delete row that contains same output
    $query = "DELETE FROM " . $table_name . " WHERE output = " . $output . "; ";
    $r = mysqli_query($link,$query);
    if (!$r)
      _exit(ERROR_QUERY, $link);    
  }
  
  // Insert new row
  $query = "INSERT INTO " . $table_name . " (input, threshold, updown, output, email, notification_interval_s, action_type, delay_s) VALUES(";
  
  // Values to insert
  $query = $query . "'" . $input . "',";
  $query = $query . $threshold . ",";
  $query = $query . $updown . ",";
  $query = $query . $output . ",";
  $query = $query . "'" . $email . "',";
  $query = $query . $notification_interval_s . ",";
  $query = $query . $action_type . ",";
  $query = $query . $delay_s . ");";

  // Execute query
  $r = mysqli_query($link,$query);
  if (!$r)
    _exit(ERROR_QUERY, $link);
}
else if ($operation == "get")// Get
{
  // Query rows
  $query = "SELECT id,input,threshold,updown,output,email,notification_interval_s, action_type,delay_s FROM " . $table_name . " ORDER BY input DESC";

  $result = mysqli_query($link, $query);
  if (!$result)
    _exit(ERROR_QUERY,$link);

  if (($n = mysqli_num_rows($result)) > 0) {
      // output data of each row
    $ids = array();
    $inputs = array();
    $thresholds = array();
    $updowns = array();
    $outputs = array();
    $emails = array();
    $notification_intervals_s = array();
    $action_types = array();
    $delays_s = array();
    $i = 0;
    while($row = mysqli_fetch_assoc($result)) 
    {
      $ids[$i] = $row["id"];
      $inputs[$i] = $row["input"];
      $thresholds[$i] = $row["threshold"];
      $updowns[$i] = $row["updown"];
      $outputs[$i] = $row["output"];
      $emails[$i] = $row["email"];
      $notification_intervals_s[$i] = $row["notification_interval_s"];
      $action_types[$i] = $row["action_type"];
      $delays_s[$i] = $row["delay_s"];
      $i = $i + 1;
    }    
    mysqli_free_result($result);
  }

  // Return values
  if(!isset($_POST['arduino']))
  {
    echo("emails(");
    for($i = 0; $i < $n; $i++)
    {
      echo($emails[$i]);
      if($i < $n - 1)
        echo(",");
    }
    echo(")");

    echo("inputs(");
    for($i = 0; $i < $n; $i++)
    {
      echo($inputs[$i]);
      if($i < $n - 1)
        echo(",");
    }
    echo(")");

  } 
  echo("{");  

  echoChecksum($cs,"ids(");
  for($i = 0; $i < $n; $i++)
  {
    echoChecksum($cs,$ids[$i]);
    if($i < $n - 1)
      echoChecksum($cs,",");
  }
  echoChecksum($cs,")");
  
  echoChecksum($cs,"n(" . $n . ")");

  echoChecksum($cs,"inputs_types(");
  for($i = 0; $i < $n; $i++)
  {
    $input = $inputs[$i];
    if (strpos($input, 'di') !== false)
    {
      echoChecksum($cs,TYPE_DIGITAL);
    }
    else
    {
      echoChecksum($cs,TYPE_ANALOG);
    }
    if($i < $n - 1)
      echoChecksum($cs,",");
  }
  echoChecksum($cs,")");

  echoChecksum($cs,"inputs_numbers(");
  for($i = 0; $i < $n; $i++)
  {
    $input = $inputs[$i];
    echoChecksum($cs,substr($input,2));
    if($i < $n - 1)
      echoChecksum($cs,",");
  }
  echoChecksum($cs,")");

  echoChecksum($cs,"thresholds(");
  for($i = 0; $i < $n; $i++)
  {
    echoChecksum($cs,$thresholds[$i]);
    if($i < $n - 1)
      echoChecksum($cs,",");
  }
  echoChecksum($cs,")");

  echoChecksum($cs,"updowns(");
  for($i = 0; $i < $n; $i++)
  {
    echoChecksum($cs,$updowns[$i]);
    if($i < $n - 1)
      echoChecksum($cs,",");
  }
  echoChecksum($cs,")");

  echoChecksum($cs,"outputs(");
  for($i = 0; $i < $n; $i++)
  {
    echoChecksum($cs,$outputs[$i]);
    if($i < $n - 1)
      echoChecksum($cs,",");
  }
  echoChecksum($cs,")");

  echoChecksum($cs,"notification_intervals_s(");
  for($i = 0; $i < $n; $i++)
  {
    echoChecksum($cs,$notification_intervals_s[$i]);
    if($i < $n - 1)
      echoChecksum($cs,",");
  }
  echoChecksum($cs,")");

  echoChecksum($cs,"action_types(");
  for($i = 0; $i < $n; $i++)
  {
    echoChecksum($cs,$action_types[$i]);
    if($i < $n - 1)
      echoChecksum($cs,",");
  }
  echoChecksum($cs,")");

  echoChecksum($cs,"delays_s(");
  for($i = 0; $i < $n; $i++)
  {
    echoChecksum($cs,$delays_s[$i]);
    if($i < $n - 1)
      echoChecksum($cs,",");
  }
  echoChecksum($cs,")");

  // Calculate checksum
  $md5 = hash('md5',$cs);
  echo("md5(" . $md5 . ")");
}

else if ($operation == "delete")
{
  //Check for expected POST arguments
  if (!isset($_POST['delete_id']))
    _exit(ERROR_ARGUMENTS, $link);
  
  // Fetch arguments
  $delete_id = $_POST['delete_id'];

  // Delete row that contains same output
  $query = "DELETE FROM " . $table_name . " WHERE id = " . $delete_id . "; ";
  $result = mysqli_query($link, $query);
  if (!$result)
    _exit(ERROR_QUERY,$link);
}

else if ($operation == "email")
{
  echo("{");
  // Argument check
  if (!isset($_POST['action_id']))
    _exit(ERROR_ARGUMENTS, $link);

  // Fetch arguments
  $action_id = $_POST['action_id'];

  // Query database
  $query = "SELECT email, input, threshold, updown FROM " . $table_name . " WHERE id = " . $action_id;
  $result = mysqli_query($link, $query);
  if (!$result)
    _exit(ERROR_QUERY,$link);

  if($row = mysqli_fetch_assoc($result))
  {
    // Get results  
    $email = $row['email'];
    $updown = $row['updown'];
    $input = $row['input'];
    $threshold = $row['threshold'];

    // Compose email
    $message = "Entrada " . $input . " ha pasado el threshold " . $threshold . " " ;
    if ($updown == 0)
      $message = $message . "arriba";
    else
      $message = $message . "abajo";
    $subject = "Alerta";
    $header = "From: Alerta PLC Monitor";
    // send email
    mail($email,$subject,$message,$header);
  }
  mysqli_free_result($result);
}

// Close MySQL connection
_exit(OK, $link); 
?>