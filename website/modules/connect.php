<?php
/**
* Database Connection implementation.
*/

include_once( dirname(__FILE__) . '/definitions.php');

/**
 * Database Connection class.
 *
 * Common functions to connect to a MySQL database.
 */
class DbConnection
{
	/** 
	 * Establish connection with database in default server 
	 *
	 * @param mixed $db Database to connect to. Default database as default argument.
	 */
	public static function connectToDatabase(&$connection, $db = DATABASE)
	{
		$connection = mysqli_connect(SERVER, USERNAME, PASSWORD, $db);

		if (mysqli_connect_errno())
			return ERROR_CONNECTION;

		return OK;
	}

	/** 
	 * Check if table exists. 
	 *
	 * @param {out}mixed $connection Database connection object.
	 * @param mixed $table Table name.
	 * @param {out}mixed $exists True if table exists. False otherwise.
	 */
	public static function tableExists(&$connection, $table, &$exists)
	{
		$query = "SELECT * FROM information_schema.tables WHERE table_name = '{$table}' LIMIT 1;";
		$r = mysqli_query($connection, $query);
		if(!$r)
			return ERROR_QUERY;

		$exists = mysqli_fetch_row($r) ? True : False;
		mysqli_free_result($r);
		return OK;
	}

	/** 
	 * Check if table is empty. Assumes table exists 
	 *
	 * @param {out}mixed $connection Database connection object.
	 * @param mixed $table Table name.
	 * @param {out}mixed $empty True if table is empty. False otherwise.
	 */
	public static function tableEmpty(&$connection, $table, &$empty)
	{
		$query = "SELECT 1 FROM {$table}";
		$r = mysqli_query($connection, $query);
		if(!$r)
			return ERROR_QUERY;
		
		$empty = mysqli_fetch_row($r) ? False : True;
		mysqli_free_result($r);
		return OK;
	}
}
?>
