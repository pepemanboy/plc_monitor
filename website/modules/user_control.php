<?php
/**
 * User control module implementation.
 */

session_start();

include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");
include_once("module.php");

/**
 * User control module.
 *
 * Contains session logging functions, as well as user management database table operations.
 *
 * Permissions are given as bitflags defined in definitions.php
 */
class UserControl extends Module
{
	/*** CLASS VARIABLES*/

	/** @var string Default admin username. */
	private $admin_name = "admin";
	/** @var string Default admin password. */
	private $admin_pass = "admin";

    /** 
     * Initialize module.
     *
     * Creates users table in database if it does not exists. 
     * Table contains:
     * * autoincrement integer user_id
     * * string username (unique)
     * * string password
     * * integer permissions
     * 
     * Adds admin user if table is empty.
     *
     * @return integer Error code
     */
    protected function initialize()
    {
    	$this->table_name = "plc_users";
		$exists = False;
		$r = tableExists($this->link, $this->table_name, $exists); 
		if($r != OK)
			return $r;

		if (!$exists)
		{
		     $query = "
		     CREATE TABLE {$this->table_name} (
		     	user_id int(11) NOT NULL auto_increment,
		        username VARCHAR(60) NOT NULL,
		        password VARCHAR(32) NOT NULL,
		        permissions int(11) NOT NULL,
		        PRIMARY KEY (user_id),
		        UNIQUE (username)
			)
			";
			$r = mysqli_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;
		}

		$empty = False;
		$r = tableEmpty($this->link, $this->table_name, $empty);
		if($r != OK)
			return $r;

		if ($empty)
		{
			$r = $this->createUser($this->admin_name, $this->admin_pass, (PERMISSIONS_OUTPUTS | PERMISSIONS_ACTIONS));
			if (!$r)
				return ERROR_QUERY;
		}

		return OK;
    }

    /**
	 * Post Request handler.
	 *
	 * When accessed through a POST request, given a POST parameter "operation", execute a given function.
	 * * operation | function
	 * * "get_user_table" | postGetUserTable
	 * * "modify_user" | postModifyUser
	 * * "add_user" | postAddUser
	 * * "remove_user" | postRemoveUser
	 * * "logout" | logOut
	 */
	protected function postRequestData($operation, &$message)
	{
		switch ($operation) 
		{
		    case "get_user_table": return $this->postGetUserTable($message);
		    case "modify_user": return $this->postModifyUser($message);
		    case "add_user": return $this->postAddUser($message);
		    case "remove_user": return $this->postRemoveUser($message);
		    case "logout": return $this->logOut();
		    default: return ERROR_ARGUMENTS; 
	    }
	}

	/*** PRIVATE FUNCTIONS */

	/**
	 * Get user table.
	 *
	 * Get the user table. Message contains the following:
	 * * table(html_table_format)
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function postGetUserTable(&$message)
	{
		$query = "SELECT user_id,username, password, permissions FROM  {$this->table_name} ORDER BY user_id ASC"; 
		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		$user_ids = array();
		$usernames = array();
		$passwords = array();
		$outputs = array();
		$actions = array();

		if (($n = mysqli_num_rows($result)) > 0) 
		{
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

		$p = "";
		for($i = 0; $i < count($usernames); $i++)
		{
			$id = $user_ids[$i];
			$user = $usernames[$i];
			$pass = $passwords[$i];
			$out = $outputs[$i];
			$act = $actions[$i];

			$p .= "
			<tr id = 'manager-row-{$id}'>
		      <td id = 'manager-user{$id}'>{$user}</td>
		      <td id = 'manager-pass{$id}'>{$pass}</td>
		      <td id = 'manager-out{$id}'>{$out}</td>
		      <td id = 'manager-act{$id}'>{$act}</td>
		      <td>
		        <button type='button' class='btn btn-warning manager-modificar-boton' data-user-number = '{$id}' id = 'manager-modificar-boton-{$id}' data-toggle='modal' data-target='#manager-modificar-modal'>Modificar</button>
		      </td>
		      <td>
		      ";
			if (strcmp($user, $admin_name) != 0)
			{
				$p .= "<button type='button' class='btn btn-danger manager-borrar-boton' data-user-number = '{$id}' id = 'modal-borrar-boton-{$id}' data-toggle='modal' data-target='#manager-borrar-modal'>Borrar</button>";
			}
		    $p .= "
		      </td>
		    </tr>
			";		
		}
		
		$this->setParameter("table", $p, $message);
		return OK;
	}

	/**
	 * Modify user in users table.
	 *
	 * Using the following POST parameters, modify the user that corresponds to the user_id:
	 * * "user_id"
	 * * "username"
	 * * "password"
	 * * "permissions"
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function postModifyUser(&$message)
	{
		$b = True;
		$user_id = $username = $password = $permissions = null;
		$b = $b && $this->getPostParameter("user_id", $user_id);
		$b = $b && $this->getPostParameter("username", $username);
		$b = $b && $this->getPostParameter("password", $password);
		$b = $b && $this->getPostParameter("permissions", $permissions);
		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "
		UPDATE {$this->table_name}
		SET username = '{$username}', password = '{$password}', permissions = {$permissions}
		WHERE user_id = {$user_id};
		";

		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
	}

	/**
	 * Add user to users table.
	 *
	 * Using the following POST parameters, add a user to the table:
	 * * "username"
	 * * "password"
	 * * "permissions"
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function postAddUser(&$message)
	{
		$b = True;
		$username = $password = $permissions = null;
		$b = $b && $this->getPostParameter("username", $username);
		$b = $b && $this->getPostParameter("password", $password);
		$b = $b && $this->getPostParameter("permissions", $permissions);
		if (!$b)
			return ERROR_ARGUMENTS;

		$r = $this->createUser($username, $password, $permissions);

		return $r;
	}

	/**
	 * Remove user from users table.
	 *
	 * Using the "user_id" POST parameters, remove the corresponding user from the users table.
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function postRemoveUser(&$message)
	{
		$b = True;
		$user_id = null;
		$b = $b && $this->getPostParameter("user_id", $user_id);
		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "
		DELETE FROM {$this->table_name}
		WHERE user_id = {$user_id};
		";

		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
	}

    /**
	 * Create user in users table.
	 *
	 * Given username, password, and permissions, add a user to the users table.
	 * Username must be unique, otherwise it will fail.
	 * 
	 * @param string $username
	 * @param string $password
	 * @param integer $permissions
	 * @return integer Error code
	 */
	private function createUser($username, $password, $permissions)
	{
		$query = "
		INSERT INTO {$this->table_name} (username, password, permissions) 
		VALUES ('{$username}','{$password}', {$permissions})
		";

		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
	}

	/*** PUBLIC FUNCTIONS */

	/**
	 * Session log in.
	 *
	 * Using pre-validated user, set session variables accordingly:
	 * * "user"
	 * * "permissions"
	 * 
	 * @param string $user Username
	 * @param integer $permissions Bitcoded user permissions
	 */
	public static function logIn($user, $permissions)
	{
	    $_SESSION["user"] = $user;
	    $_SESSION["permissions"] = $permissions;
	}

	/**
	 * Session log out.
	 *
	 * Destroy session and session variables.
	 */
	public static function logOut()
	{
		session_destroy();
		$_SESSION = [];
	}

	/**
	 * Check if session has admin permissions.
	 *
	 * Check if user is equal to "admin"
	 * 
	 * @return boolean True if admin, else False.
	 */
	public static function adminSession()
	{
		if (!isset($_SESSION["user"]))
			return False;
		return $_SESSION["user"] == ADMIN_USER;
	}

	/**
	 * Check if current session has certain permission.
	 *
	 * Given the permissions parameter, compare it to see if the current session has this permission bitflag set.
	 *
	 * @param integer $permission
	 * 
	 * @return boolean True if valid, else False.
	 */
	public static function validatePermissions($permission)
	{
		if(!isset($_SESSION["permissions"]))
			return False;
		return ($_SESSION["permissions"] & $permission) == 0 ? False : True;
	}

	/**
	 * Check if session is not empty.
	 *
	 * Will redirect to login.php if session not set.
	 * 
	 * @return integer Error code.
	 */
	public static function validateSession()
	{
		if (!isset($_SESSION["user"]))
		{
			echo("<meta http-equiv='refresh' content='0; url=login.php' />");
			return ERROR_SESSION;
		}
		return OK;
	}

	/**
	 * Validate in users table if username and password match.
	 * 
	 * @param string $username
	 * @param string $password
	 * @param {out}integer $permissions Permissions of the registered user
	 * @return integer Error code
	 */
	public function validateUserPass($username, $password, &$permissions)
	{
		if (!$this->initialized())
			return ERROR_INITIALIZE;

		$query = "
		SELECT username, password, permissions FROM {$this->table_name} WHERE username = '{$username}' AND password = '{$password}'
		";

		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

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

}
?>