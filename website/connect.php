<?php

/** Common definiitions */
include("definitions.php");

/** Establish connection with mySql server */
function connectToServer(&$connection, $server = SERVER, $user = USERNAME, $pass = PASSWORD){

	$connection = mysqli_connect($server, $user, $pass);

	// Check connection
	if (mysqli_connect_errno())
	{
		_debug("Failed to connect to MySQL: " . mysqli_connect_error());
		return ERROR_CONNECTION;
	}

	return OK;
}

/** Select a database */
function selectDatabase(&$connection, $db)
{
	$r = mysqli_select_db($connection, $db);
	if(!$r)
	{
		_debug("Failed to connect to database: " . mysqli_error($connection));
		return ERROR_SELECT_DB;
	}
	return OK;
}

/** Establish connection with database in default server */
function connectToDatabase(&$connection, $db = DATABASE)
{
	$connection = mysqli_connect(SERVER, USERNAME, PASSWORD, $db);
	// Check connection
	if (mysqli_connect_errno())
	{
		_debug("Failed to connect to MySQL and database: " . mysqli_connect_error());
		return ERROR_CONNECTION;
	}
	return OK;
}

/** Mysql query */
function queryDatabase(&$connection, $query)
{
	$val= mysqli_query($connection, $query);
	if(!$val)
	{
		_debug("Failed to query: ". mysqli_error($connection));
		return ERROR_QUERY;
	}
	return OK;
}

/** Check if table exists */
function tableExists(&$connection, $table, &$exists)
{
	$query = "SELECT /*+ MAX_EXECUTION_TIME(1000) */ * FROM information_schema.tables WHERE table_name =  '". $table ."' LIMIT 1;";
	$r = mysqli_query($connection, $query);
	if(!$r)
	{
		_debug("Failed to query: ". mysqli_error($connection));
		return ERROR_QUERY;
	}
	$exists = mysqli_fetch_row($r) ? True : False;
	mysqli_free_result($r);
	return OK;
}

/** Check if table is empty. Assumes table exists */
function tableEmpty(&$connection, $table, &$empty)
{
	$query = "SELECT /*+ MAX_EXECUTION_TIME(1000) */ 1 FROM `" . $table . "`";
	$r = mysqli_query($connection, $query);
	if(!$r)
	{
		_debug("Failed to query: ". mysqli_error($connection));
		return ERROR_QUERY;
	}
	$empty = mysqli_fetch_row($r) ? False : True;
	mysqli_free_result($r);
	return OK;
}

?>
