<?php 

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

// Constants
$TABLE_NAME = "plc_users";
$ADMIN_NAME = "admin";
$ADMIN_PASS =  "admin";

/**
*	Log in session
*	@return error code
*/
function logIn()
{
	return OK;
}

/**
*	Log out session
*	@return error code
*/
function logOut()
{
	return OK;
}

/**
*	Validate session
*	@param session
*	@return error code
*/
function validateSession()
{
	session_start();
	echo("<meta http-equiv='refresh' content='0; url=login.php' />");
	return ERROR_SESSION;
}

/**
*	Validate username and password on table
*	@param connection database connection
*	@param username
*	@param password
*	@return error code
*/
function validateUserPass(&$connection, $username, $password)
{
	// Assert connection
	if (!$connection)
		return ERROR_CONNECTION;

	// Assert table
	$r = createUserControlTable($connection);
	if($r != OK)
		return $r;

	// Query table
	$query = "
	SELECT username, password FROM " . $table_name . " WHERE username = '" . $username . "' AND password = '" . $password . "'
	";

	$result = mysqli_query($link, $query);
	if (!$result)
		return $result;

	// Compare username and password
	if (($n = mysqli_num_rows($result)) > 0) 
	{
		$row = mysqli_fetch_assoc($result);
		$user = $row["username"];
		$pass = $row["password"];		
		mysqli_free_result($result);
		if ($user != $username || $pass != $password)
			return ERROR_USERPASS;
	}
	else
		return ERROR_USERPASS;

	return OK;
}

/**
*	Insert user into table
*	@param connection Database connection
*	@param username
*	@param password
*	@param permissions
*	@return error code
*/
function createUser(&$connection, $username, $password, $permissions)
{
	// Assert connection
	if (!$connection)
		return ERROR_CONNECTION;

	// Assert table
	$r = createUserControlTable($connection);
	if($r != OK)
		return $r;

	// Insert user
	$query = "
	INSERT INTO plc_users (username, password, permissions) 
	VALUES ('" . $username . "','" . $password . "', " . $permissions . ")
	";

	$r = mysqli_query($link,$query);
	if (!$r)
		return $r;

	return OK;
}

/**
*	Create users control table if it doesnt exist. 
*	If it exists and is empty, create admin user.
*	@param connection mysql connection
*	@return error code
*/
function createUserControlTable(&$connection)
{
	// Assert connection
	if (!$connection)
		return ERROR_CONNECTION;

	// Query table existent
	$exists = False;
	$r = tableExists($connection, $TABLE_NAME, $exists); 
	if($r != OK)
		return $r;

	// Create table if it doesnt exist
	if (!$exists)
	{
	     $query = "
	     CREATE TABLE " . $table_name . " (
	     	user_id int(11) NOT NULL auto_increment,
	        username VARCHAR(60) NOT NULL,
	        password VARCHAR(32) NOT NULL,
	        permissions int(11) NOT NULL,
	        PRIMARY KEY (user_id),
	        UNIQUE (username)
		)
		";
		$r = mysqli_query($link,$query);
		if (!$r)
			return $r;
	}

	// Query table empty
	$empty = False;
	$r = tableEmpty($link, $table_name, $empty);
	if($r != OK)
		return $r;

	// Insert one row if it doesnt exist
	if ($empty)
	{
		$query = "
		INSERT INTO " . $table_name . " 
		(user_id, username, password, permissions) 
		VALUES (0,'" . $ADMIN_NAME . "','" . $ADMIN_PASS . "',128);
		";  	

		$r = mysqli_query($link,$query);
		if (!$r)
			return $r;
	}

	return OK;
}
?>
