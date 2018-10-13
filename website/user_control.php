<?php 
session_start();

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

// Constants
$TABLE_NAME = "plc_users";
$ADMIN_NAME = ADMIN_USER;
$ADMIN_PASS = "admin";
$ADMIN_ID = 0;

/**
*	Log in session
*	@return error code
*/
function logIn($user, $permissions)
{
    $_SESSION["user"] = $user;
    $_SESSION["permissions"] = $permissions;
	return OK;
}

/**
*	Log out session
*	@return error code
*/
function logOut()
{
	session_destroy();
	$_SESSION = [];
	return OK;
}

/**
*	Validate admin session
*/
function adminSession()
{
	if (!isset($_SESSION["user"]))
		return False;
	return $_SESSION["user"] == ADMIN_USER;
}

/**
*	Validate permissions
*/
function validatePermissions($permission)
{
	if(!isset($_SESSION["permissions"]))
		return False;
	return ($_SESSION["permissions"] & $permission) == 0 ? False : True;
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
function validateUserPass($username, $password, &$permissions)
{
	// Connect to server and database
	$link = null;
	$r = connectToDatabase($link);
	if ($r != OK)
		return __exit($r, $link);

	$r = _validateUserPass($link, $username, $password, $permissions);
		
	return __exit($r, $link);
}

/**
*	Validate username and password on table
*	@param connection database connection
*	@param username
*	@param password
*	@return error code
*/
function _validateUserPass(&$connection, $username, $password, &$permissions)
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
	SELECT username, password, permissions FROM " . $table_name . " WHERE username = '" . $username . "' AND password = '" . $password . "'
	";

	$result = mysqli_query($connection, $query);
	if (!$result)
		return ERROR_QUERY;

	// Compare username and password
	if (($n = mysqli_num_rows($result)) > 0) 
	{
		$row = mysqli_fetch_assoc($result);
		$user = $row["username"];
		$pass = $row["password"];		
		mysqli_free_result($result);
		if ($user != $username || $pass != $password)
			return ERROR_USERPASS;
		$permissions = $row["permissions"];
	}
	else
		return ERROR_USERPASS;

	return OK;
}

/**
*	Insert user into table
*	@param link Database connection
*	@param username
*	@param password
*	@param permissions
*	@return error code
*/
function createUser(&$link, $username, $password, $permissions)
{
	$table_name = $GLOBALS['TABLE_NAME'];

	// Insert user
	$query = "
	INSERT INTO " . $table_name . " (username, password, permissions) 
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
*	@param link mysql connection
*	@return error code
*/
function createUserControlTable(&$link)
{
	$table_name = $GLOBALS['TABLE_NAME'];
	$admin_name = $GLOBALS['ADMIN_NAME'];
	$admin_pass = $GLOBALS['ADMIN_PASS'];
	$admin_id = $GLOBALS['ADMIN_ID'];

	// Assert connection
	if (!$link)
		return ERROR_CONNECTION;

	// Query table existent
	$exists = False;
	$r = tableExists($link, $table_name, $exists); 
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
	$r = tableEmpty($link, $table_name, $empty);
	if($r != OK)
		return $r;

	// Insert one row if it doesnt exist
	if ($empty)
	{
		$r = createUser($link, $admin_name, $admin_pass, (PERMISSIONS_OUTPUTS | PERMISSIONS_ACTIONS));
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
		return ERROR_QUERY;

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
			$user_ids[$i] = $row["user_id"];
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
	      <td id = 'manager-user" . $id . "'>" . $user . "</td>
	      <td id = 'manager-pass" . $id . "'>" . $pass . "</td>
	      <td id = 'manager-out" . $id . "'>" . $out . "</td>
	      <td id = 'manager-act" . $id . "'>" . $act . "</td>
	      <td>
	        <button type='button' class='btn btn-warning manager-modificar-boton' data-user-number = '" . $id . "' id = 'manager-modificar-boton-" . $id . "' data-toggle='modal' data-target='#manager-modificar-modal'>Modificar</button>
	      </td>
	      <td>
	      ";
		if (strcmp($user, $admin_name) != 0)
		{
			$message .= "<button type='button' class='btn btn-danger manager-borrar-boton' data-user-number = '" . $id . "' id = 'modal-borrar-boton-" . $id . "' data-toggle='modal' data-target='#manager-borrar-modal'>Borrar</button>";
		}
	    $message .= "
	      </td>
	    </tr>
		";		
	}
	$message .= ")";
	return OK;
}

function userControlModifyUser(&$link, &$message)
{
	$table_name = $GLOBALS['TABLE_NAME'];

	$user_id = $_POST["user_id"];
	$username = $_POST["username"];
	$password = $_POST["password"];
	$permissions = $_POST["permissions"];

	$query = "
	UPDATE " . $table_name . "
	SET username = '" . $username . "', password = '" . $password . "', permissions = " . $permissions . "
	WHERE user_id = " . $user_id . ";
	";

	$r = mysqli_query($link, $query);
	if (!$r)
		return ERROR_QUERY;

	return OK;
}

/**
*	Add user to database table
*/
function userControlCreateUser(&$link, &$message)
{
	$table_name = $GLOBALS['TABLE_NAME'];

	$user_id = $_POST["user_id"];
	$username = $_POST["username"];
	$password = $_POST["password"];
	$permissions = $_POST["permissions"];

	$r = createUser($link, $username, $password, $permissions);

	return $r;
}

/**
*	Remove user from table
*/
function userControlRemoveUser(&$link, &$message)
{
	$table_name = $GLOBALS['TABLE_NAME'];

	$user_id = $_POST["user_id"];

	$query = "
	DELETE FROM " . $table_name . "
	WHERE user_id = " . $user_id . ";
	";

	$r = mysqli_query($link, $query);
	if (!$r)
		return ERROR_QUERY;

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
		return;

	$operation = $_POST["operation"];

	// Connect to server and database
	$link = null;
	$r = connectToDatabase($link);
	if ($r != OK)
		_exit($r, $link);

	// Assert table
	$r = createUserControlTable($link);
	if($r != OK)
		_exit($r, $link);

	$message = "";
	switch ($operation) 
	{
	    case "get_user_table": $r = userControlGetUserTable($link, $message); break;
	    case "modify_user": $r = userControlModifyUser($link, $message); break;
	    case "add_user": $r = userControlCreateUser($link, $message); break;
	    case "remove_user": $r = userControlRemoveUser($link, $message); break;
	    case "logout": $r = logOut(); break;
	    default: break;
    }

    if ($r != OK)
    	_exit($r, $link);

    echo($message);

    _exit(OK, $link);	
}

?>
