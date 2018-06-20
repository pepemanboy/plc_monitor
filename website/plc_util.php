<?php

// Includes
include_once("definitions.php");
include_once("connect.php");

/* Create PLC table */
function createPlcTable(&$connection)
{
	$query = "
	CREATE TABLE plcs (
	id int NOT NULL AUTO_INCREMENT,
	name VARCHAR(200) NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (name));
	";
	$r = mysqli_query($connection,$query);
	if (!$r)
		return ERROR_QUERY;

	return OK;
}

/* Check if PLC table exists */
function existsPlcTable(&$connection, &$exists)
{
	// Query table existent
	$exists = False;
	$table = MAIN_TABLE;
	$r = tableExists($connection, $table, $exists); 
	if($r != OK)
		return $r;
	return OK;
}

/* If table does not exist, create it */
function plcTableAssert(&$connection, &$status = False)
{
	if (!$connection)
		return ERROR_CONNECTION;
	$status = False;
	$r = existsPlcTable($connection, $status);
	if($r != OK)
		return $r;
	if(!$status)
	{
		$r = createPlcTable($connection);
		if($r != OK)
			return $r;
		$status = True;
	}
	return OK;
}

/* Add plc to table */
function addPlc(&$connection, $name)
{
	// Check for valid connection to mysql
	if (!$connection)
		return ERROR_CONNECTION;

	// Assert plc 
	$r = plcTableAssert($connection);
	if($r != OK)
		return $r;

	// Insert PLC into table
	$query = "INSERT INTO plcs (name) VALUES ('". $name . "')";
	$r = mysqli_query($connection,$query);
	if (!$r)
		return ERROR_QUERY;

	return OK;
}

/* Find plc by id, get name */
function findPlcById(&$connection, $id, &$name)
{
	// Check connection
	if (!$connection)
		return ERROR_CONNECTION;

	// Assert plc 
	$r = plcTableAssert($connection);
	if($r != OK)
		return $r;
	
	$query = "SELECT id,name FROM plcs WHERE id in (" . $id . ")";
	if ($result = mysqli_query($connection, $query)) 
	{
		// Get row
		$row = mysqli_fetch_row($result);   
		$name = $row[1];
		return OK;
	}

	return ERROR_QUERY;
}
function findPlcByName(&$connection, &$name, &$id)
{
	// Check connection
	if (!$connection)
		return ERROR_CONNECTION;

	// Assert plc 
	$r = plcTableAssert($connection);
	if($r != OK)
		return $r;
	
	$query = "SELECT id,name FROM plcs WHERE name in ('" . $name . "')";
	if ($result = mysqli_query($connection, $query)) 
	{
		// Get row
		$row = mysqli_fetch_row($result);   
		$id = $row[0];
		return OK;
	}

	return ERROR_QUERY;
}

/* Find plc by name, get id */



/* Get PLC list */
function getPlcList(&$connection, &$ids, &$names)
{
	// Check connection
	if (!$connection)
		return ERROR_CONNECTION;

	// Assert plc 
	$r = plcTableAssert($connection);
	if($r != OK)
		return $r;

	$query = "SELECT id,name FROM plcs ORDER BY id ASC";
	$result = mysqli_query($connection, $query);
	if (mysqli_num_rows($result) > 0) {
	    // output data of each row
		$i = 0;
		while($row = mysqli_fetch_row($result)) {
			$ids[$i] = $row[0];
			$names[$i] = $row[1];
			$i = $i + 1;
		}
		mysqli_free_result($result);
	} 
	return OK;
}

/* Delete PLC by id */
function deletePlc(&$connection, $id)
{
	// Check connection
	if (!$connection)
		return ERROR_CONNECTION;

	// Assert plc 
	$r = plcTableAssert($connection);
	if($r != OK)
		return $r;

	// Insert PLC into table
	$query = "DELETE FROM plcs WHERE id IN ('". $id. "')";
	$r = mysqli_query($connection,$query);
	if (!$r)
		return ERROR_QUERY;

	return OK;
}

/* Get Arduino status */
function arduinoStatus(&$connection, $id, $operation = "set", &$status = NULL)
{
	// Check connection
	if (!$connection)
		return ERROR_CONNECTION;

	// Table name
	$table_name = "plc" . $id . "_status";

	// Query table existent
	$exists = False;
	$r = tableExists($connection, $table_name, $exists); 
	if ($r != OK)
		return $r;

	// Create table if it doesnt exist
	if (!$exists)
	{
		$query = "
		CREATE TABLE " . $table_name . " (
		timeStamp TIMESTAMP NOT NULL PRIMARY KEY)
		";
		$r = mysqli_query($connection,$query);
		if (!$r)
			return ERROR_QUERY;
	}
	if ($operation == "set")
	{
		// Delete
		$query = "DELETE FROM " . $table_name;
		$r = mysqli_query($connection,$query);
		if (!$r)
			return ERROR_QUERY;

		// Log
		$query = "INSERT INTO " . $table_name . " (timeStamp) values (NULL)";
		$r = mysqli_query($connection,$query);
		if (!$r)
			return ERROR_QUERY;
	}
	else if ($operation == "get")
	{
		// Query
		$query = "SELECT timeStamp FROM " . $table_name . " ORDER BY timeStamp DESC LIMIT 1";
		$result = mysqli_query($connection, $query);
		if (!$result) 
			return ERROR_QUERY;
		
		// Get row
		$row = mysqli_fetch_assoc($result);

		if (($n = mysqli_num_rows($result)) == 0) 
			$status = "Nunca";
		else
			$status = $row['timeStamp'];

	  // Free result
	  mysqli_free_result($result);
	}	

	return OK;
}

?>
