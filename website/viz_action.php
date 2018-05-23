<?php 
/**
Visualization action
*/

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

//Check for expected POST arguments
if (empty($_POST['plc_number']) or empty($_POST['operation']))
{
  _exit(ERROR_ARGUMENTS, $link);
}

$plc_number = $_POST['plc_number'];
$suffix = "plc" . $plc_number . "_";  
$table_name = $suffix . "actions";
$operation = $_POST['operation'];

// Connect to server and database
$link = null;
$r = connectToDatabase($link);
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
  timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
  input int(11) NOT NULL,
  threshold float(5,2) NOT NULL,
  updown BIT NOT NULL,
  output int(11) NOT NULL,
  email VARCHAR(200) NOT NULL,
  notification_interval_s int(11) NOT NULL,
  action_type int(11) NOT NULL,
  delay_s int(11) NOT NULL)";
  $r = mysqli_query($link,$query);
  if (!$r)
    _exit(ERROR_QUERY, $link);
}

if ($operation == "set")
{
  // Check for arguments
  if(empty($_POST['input']) or empty($_POST['threshold']) or empty($_POST['updown']) or empty($_POST['output']) or empty($_POST['email']) or empty($_POST['notification_interval_s']) or empty($_POST['action_type']) or empty($_POST['delay_s']))
    _exit(ERROR_ARGUMENTS, $link);

  // Fetch arguments
  $input = $_POST['input'];
  $threshold = $_POST['threshold'];
  $updown = $_POST['updown'];
  $output = $_POST['output'];
  $email = $_POST['email'];
  $notification_interval_s = $_POST['notification_interval_s'];
  $action_type = $_POST['action_type'];
  $delay_s = $_POST['delay_s'];

  // Delete row that contains same output
  $query = "DELETE FROM " . $table_name . " WHERE output = " . $output . "; ";
  
  // Insert new row
  $query = $query . "INSERT INTO " . $table_name . " (input, threshold, updown, output, email, notification_interval_s, action_type, delay_s) VALUES(";
  
  // Values to insert
  $query = $query . $input . ",";
  $query = $query . $threshold . ",";
  $query = $query . $updown . ",";
  $query = $query . $output . ",";
  $query = $query . $email . ",";
  $query = $query . $notification_interval_s . ",";
  $query = $query . $action_type . ",";
  $query = $query . $delay_s . ");";

  // Execute query
  $r = mysqli_multi_query($link, $query);
  if (!$r)
    _exit(ERROR_QUERY, $link);

  do{} while(mysqli_more_results($link) && mysqli_next_result($link)); // flush multi queries
}
else // Get
{
  // Query rows
  $query = "SELECT input,threshold,updown,output,email,notification_interval_s, action_type,delay_s FROM " . $table_name . " ORDER BY input DESC";

  $result = mysqli_query($link, $query);
  if (!$result)
    _exit(ERROR_QUERY,$link);

  if (($n = mysqli_num_rows($result)) > 0) {
      // output data of each row
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

      // Return values
    echo("inputs(");
    for($i = 0; $i < $n; $i++)
    {
      echo($inputs[$i]);
      if($i < $n - 1)
        echo(",");
    }
    echo(")");

    echo("thresholds(");
    for($i = 0; $i < $n; $i++)
    {
      echo($thresholds[$i]);
      if($i < $n - 1)
        echo(",");
    }
    echo(")");

    echo("updowns(");
    for($i = 0; $i < $n; $i++)
    {
      echo($updowns[$i]);
      if($i < $n - 1)
        echo(",");
    }
    echo(")");

    echo("outputs(");
    for($i = 0; $i < $n; $i++)
    {
      echo($outputs[$i]);
      if($i < $n - 1)
        echo(",");
    }
    echo(")");

    echo("emails(");
    for($i = 0; $i < $n; $i++)
    {
      echo($emails[$i]);
      if($i < $n - 1)
        echo(",");
    }
    echo(")");

    echo("notification_intervals_s(");
    for($i = 0; $i < $n; $i++)
    {
      echo($notification_intervals_s[$i]);
      if($i < $n - 1)
        echo(",");
    }
    echo(")");

    echo("action_types(");
    for($i = 0; $i < $n; $i++)
    {
      echo($action_types[$i]);
      if($i < $n - 1)
        echo(",");
    }
    echo(")");

    echo("delays_s(");
    for($i = 0; $i < $n; $i++)
    {
      echo($delays_s[$i]);
      if($i < $n - 1)
        echo(",");
    }
    echo(")");


    mysqli_free_result($result);
  }
}

// Close MySQL connection
_exit(OK, $link); 
?>