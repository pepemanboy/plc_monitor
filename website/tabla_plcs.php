<?php 
/**
Tabla en base de datos: plcs
*/

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

//Check for expected POST arguments
if (!isset($_POST['operation']) ) 
	_exit(ERROR_ARGUMENTS);

// Fetch arguments
$operation = $_POST['operation'];

// Table name
$table_name = "plcs";

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
	CREATE TABLE plcs (
	id int NOT NULL AUTO_INCREMENT,
	name VARCHAR(200) NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name));
	";
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}

if($operation == "add")
{
	// Check for arguments
	if (!isset($_POST['plc_name'])) 
		_exit(ERROR_ARGUMENTS);

    // Fetch arguments
	$plc_name = $_POST['plc_name']; 

	// Add PLC into table
	$query = "INSERT INTO plcs (name) VALUES ('". $plc_name . "')";
	$r = mysqli_query($link,$query);
	if (!$r)
		_exit(ERROR_QUERY, $link);
}
else if ($operation == "get")
{
	// Output format arguments
	if (!isset($_POST['format'])) 
		_exit(ERROR_ARGUMENTS);

	// Query
	$query = "SELECT id,name FROM plcs ORDER BY id ASC";
	$result = mysqli_query($link, $query);
	if (!$result)
		_exit(ERROR_QUERY,$link);

	// Get result
	$ids = array();
	$names = array();
	if (($n = mysqli_num_rows($result)) > 0) 
	{
		// Save data of each row
		$i = 0;
		while($row = mysqli_fetch_assoc($result)) 
		{
			$ids[$i] = $row["id"];
			$names[$i] = $row["name"];
			$i = $i + 1;
		}
		mysqli_free_result($result);
	}

	$status = array();
	$i = 0;
	foreach($ids as $id)
	{
		$stat = 0;
		$r = arduinoStatus($link, $id , "get" , $stat);
		if ($r != OK)
			_exit($r, $link);
		$status[$i] = $stat;
		$i = $i + 1;
	}

	// Print on selected format
	$format = $_POST['format'];
	if($format == "table") 
	{
		printTable($ids, $names, $status);
	}
	else
	{
		printArrays($ids, $names);
	}

}
else if ($operation == "delete")
{
	// Check for arguments
	if (!isset($_POST['plc_number'])) 
		_exit(ERROR_ARGUMENTS);

    // Fetch arguments
	$plc_number = $_POST['plc_number']; 

	// Fetch arguments
	$suffix = "plc" . $plc_number . "_";  
	$table_inputs = $suffix . "inputs";
	$table_outputs = $suffix . "outputs";
	$table_config = $suffix . "config";
	$table_actions = $suffix . "actions";
	$tables = array($table_config, $table_inputs, $table_outputs, $table_actions);
	for($i = 1; $i <= 6; $i ++)
		array_push($tables, $suffix . "di". $i, $suffix . "ai". $i);

	// Delete registry from main table
	$query = "DELETE FROM plcs WHERE id IN ('". $plc_number. "')";
	$result = mysqli_query($link, $query);
	if (!$result)
		_exit(ERROR_QUERY,$link);

	// Delete tables if they exist
	foreach ($tables as $table)
	{
		// Query table existent
		$exists = False;
		$r = tableExists($link, $table, $exists); 
		if($r != OK)
			_exit($r, $link);

		if(!$exists)
			continue;

		// Drop table
		$query = "DROP TABLE " . $table;
		$result = mysqli_query($link, $query);
		if (!$result)
			_exit(ERROR_QUERY,$link);
	}
}
else if ($operation == "exists")
{	
	$r = arduinoStatus($link, 1);
	if ($r != OK)
		_exit($r, $link);	
	echo("{");
	// Check for arguments
	if (!isset($_POST['plc_number'])) 
		_exit(ERROR_ARGUMENTS);

    // Fetch arguments
	$plc_number = $_POST['plc_number']; 

	// Query
	$query = "SELECT name FROM plcs WHERE id = " . $plc_number;
	$result = mysqli_query($link, $query);
	if (!$result)
		_exit(ERROR_QUERY,$link);

	$exists = 0;

	if (($n = mysqli_num_rows($result)) > 0) 
		$exists = 1;

	echo("exists(" . $exists . ")");
}

// Format the output as table
function printTable($ids, $names, $status)
{
	echo("table(");
	for($i = 0; $i < count($ids); $i++)
	{
		$name = $names[$i];
		$id = $ids[$i];
		$stat = $status[$i];
		// Echo row
		echo("<tr id = 'admin-row-" . $id . "'>
	      <th scope='row'>". $id ."</th>
	      <td>" . $name . "</td>
	      <td>" . $stat . "</td>
	      <td>
	        <button type='button' class='btn btn-danger admin-borrar-boton' data-plc-number = '" . $id . "' id = 'admin-borrar-boton-" . $id . "' data-toggle='modal' data-target='#admin-borrar-modal'>Borrar</button>
	      </td>
	    </tr>
		");		
	}
	echo(")");
}

// Format the output as arrays
function printArrays($ids, $names)
{
	// Return values
	$n = count($ids);
	echo("ids(");
	for($i = 0; $i < $n; $i++)
	{
		echo($ids[$i]);
		if($i < $n - 1)
			echo(",");
	}
	echo(")");
	echo("names(");
	for($i = 0; $i < $n; $i++)
	{
		echo($names[$i]);
		if($i < $n - 1)
			echo(",");
	}
	echo(")");
}

// Close MySQL connection
_exit(OK, $link); 
?>

