<?php 
/**
Visualization graph
*/
// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

//Check for expected POST arguments
if (empty($_POST['signal_number']) or empty($_POST['signal_type']) or empty($_POST['plc_number']) or empty($_POST['operation']))
{
	_exit(ERROR_ARGUMENTS, $link);
}

$plc_number = $_POST['plc_number'];
$signal_number = $_POST['signal_number'];
$signal_type = $_POST['signal_type'];
$suffix = "plc" . $plc_number . "_";  
$table_name = $suffix . $signal_type . $signal_number;
$operation = $_POST['operation'];

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
	timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
	val float(5,2) NOT NULL)";
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

// Insert value
if ($operation == "set")
{
	echo("{");
	if (!isset($_POST['value'])) 
		_exit(ERROR_ARGUMENTS, $link);

	// Fetch arguments
	$val = $_POST['value'];  

	$query = "INSERT INTO " . $table_name . " (val) VALUES (" . $val . ")";
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}
// Get values
else
{
	// Check arguments
	if (empty($_POST['date_start']) or empty($_POST['date_end'])) 
		_exit(ERROR_ARGUMENTS, $link);

	$date_start = $_POST['date_start'];
	$date_end = $_POST['date_end'];

	// Query inputs
	$query = "SELECT * FROM " . $table_name . "
 		WHERE timeStamp >='" . $date_start . "'
   		AND timeStamp < '" . $date_end . "' 
   		ORDER BY timeStamp";

   	$result = mysqli_query($link, $query);
   	if (!$result)
   		_exit(ERROR_QUERY,$link);
   	
   	if (($n = mysqli_num_rows($result)) > 0) {
	    // output data of each row
	    $values = array();
	    $dates = array();
	    $i = 0;
	    while($row = mysqli_fetch_assoc($result)) 
	    {
	        $values[$i] = $row["val"];
	        $dates[$i] = $row["timeStamp"];
	        $i = $i + 1;
	    }

	    // Return values
	    echo("values(");
	    for($i = 0; $i < $n; $i++)
	    {
	    	echo($values[$i]);
	    	if($i < $n - 1)
	    		echo(",");
	    }
	    echo(")");

	    // Return dates
	    echo("dates(");
	    for($i = 0; $i < $n; $i++)
	    {
	    	echo($dates[$i]);
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

