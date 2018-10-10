<?php 

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

// Constants
$TABLE_NAME = "plc_users";
$ADMIN_NAME = "admin";
$ADMIN_PASS = "admin";

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
*	Validate admin session
*/
function adminSession()
{
	if (!isset($_SESSION["admin"]))
		return False;
	return $_SESSION["admin"] == True;
}

/**
*	Validate session
*	@param session
*	@return error code
*/
function validateSession()
{
	if (!isset($_SESSION["user"]))
	{
		echo("<meta http-equiv='refresh' content='0; url=login.php' />");
		return ERROR_SESSION;
	}
	return OK;
}

/**
*	Validate username and password
*	@param username
*	@param password
*	@return error code
*/
function validateUserPass($username, $password)
{
	// Connect to server and database
	$link = null;
	$r = connectToDatabase($link);
	if ($r != OK)
		return __exit($r, $link);

	$r = _validateUserPass($link, $username, $password);
		
	return __exit($r, $link);
}

/**
*	Validate username and password on table
*	@param connection database connection
*	@param username
*	@param password
*	@return error code
*/
function _validateUserPass(&$connection, $username, $password)
{
	$table_name = $GLOBALS['TABLE_NAME'];

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

	$result = mysqli_query($connection, $query);
	if (!$result)
		return "ERROR POPO";

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
		return ERROR_QUERY;

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
	$table_name = $GLOBALS['TABLE_NAME'];
	$admin_name = $GLOBALS['ADMIN_NAME'];
	$admin_pass = $GLOBALS['ADMIN_PASS'];

	// Assert connection
	if (!$connection)
		return ERROR_CONNECTION;

	// Query table existent
	$exists = False;
	$r = tableExists($connection, $table_name, $exists); 
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
			return ERROR_QUERY;
	}

	// Query table empty
	$empty = False;
	$r = tableEmpty($connection, $table_name, $empty);
	if($r != OK)
		return $r;

	// Insert one row if it doesnt exist
	if ($empty)
	{
		$query = "
		INSERT INTO " . $table_name . " 
		(user_id, username, password, permissions) 
		VALUES (0,'" . $admin_name . "','" . $admin_pass . "',128);
		";  	

		$r = mysqli_query($connection,$query);
		if (!$r)
			return ERROR_QUERY;
	}

	return OK;
}

function userControlGetUserTable(&$link, &$message)
{
	$table_name = $GLOBALS['TABLE_NAME'];
	$admin_name = $GLOBALS['ADMIN_NAME'];
	$admin_pass = $GLOBALS['ADMIN_PASS'];

	// Query
	$query = "SELECT user_id,username, password, permissions FROM  " . $table_name . " ORDER BY user_id ASC"; 

	$result = mysqli_query($link, $query);
	if (!$result)
		return __exit(ERROR_QUERY,$link);

	// Initialize variables
	$user_ids = array();
	$usernames = array();
	$passwords = array();
	$outputs = array();
	$actions = array();

	// Get result
	if (($n = mysqli_num_rows($result)) > 0) 
	{
		// Save data of each row
		$i = 0;
		while($row = mysqli_fetch_assoc($result)) 
		{
			$ids[$i] = $row["user_id"];
			$usernames[$i] = $row["username"];
			$passwords[$i] = $row["password"];
			$p = (int)$row["permissions"];
			$outputs[$i] = ($p & PERMISSIONS_OUTPUTS) ? "yes" : "no";
			$actions[$i] = ($p & PERMISSIONS_ACTIONS) ? "yes" : "no";
			$i = $i + 1;
		}
		mysqli_free_result($result);
	}

	// Print table
	$message .= "table(";
	for($i = 0; $i < count($usernames); $i++)
	{
		$id = $user_ids[$i];
		$user = $usernames[$i];
		$pass = $passwords[$i];
		$out = $outputs[$i];
		$act = $actions[$i];

		// Echo row
		$message .= "
		<tr id = 'manager-row-" . $id . "'>
	      <td>" . $user . "</td>
	      <td>" . $pass . "</td>
	      <td>" . $out . "</td>
	      <td>" . $act . "</td>
	      <td>
	        <button type='button' class='btn btn-warning manager-modificar-boton' data-user-number = '" . $id . "' id = 'manager-modificar-boton-" . $id . "' >Modificar</button>
	      </td>
	      <td>
	        <button type='button' class='btn btn-danger manager-borrar-boton' data-user-number = '" . $id . "' id = 'manager-borrar-boton-" . $id . "' data-toggle='modal' data-target='#manager-borrar-modal'>Borrar</button>
	      </td>
	    </tr>
		";		
	}
	$message .= ")";
	return OK;
}

/** 
*	Process POST request
*/
function userControlPostRequest()
{
	if ($_SERVER["REQUEST_METHOD"] != "POST")
		return;

	if(!isset($_POST["operation"]))
		_exit(ERROR_ARGUMENTS, null);

	$operation = $_POST["operation"];

	// Connect to server and database
	$link = null;
	$r = connectToDatabase($link);
	if ($r != OK)
		_exit($r, $link);

	$message = "";
	switch ($operation) 
	{
	    case "get_user_table": $r = userControlGetUserTable($link, $message); break;
	    default: break;
    }

    if ($r != OK)
    	_exit($r, $link);

    echo($message);

    _exit(OK, $link);	
}

/** 
*	Post
*/
userControlPostRequest();



?>
