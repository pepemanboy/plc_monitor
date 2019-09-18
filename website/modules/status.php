<?php
/**
 * PLC status module implementation.
 */

if(session_status() == PHP_SESSION_NONE)
    session_start();

include_once( dirname(__FILE__) . '/module.php');
include_once( dirname(__FILE__) . '/tabla_plcs.php');

/**
 * PLC status module.
 *
 * Contains functions to get and set the status of a PLC.
 */
class PLCStatus extends Module
{
	/**
	 * PLCstatus handler
	 *
	 * @param integer $id PLC Id.
	 * @param string $operation "set" or "get".
	 * @param string $status last online date.
	 */
	public function status($id, $operation = "set", &$status = null)
	{
		if (!$this->initialized())
			return ERROR_INITIALIZE;

		$this->table_name = "plc{$id}_status";

		$plc_table = new TablaPlcs();
		$r = $plc_table->findPlcById($id);
		if ($r != OK)
		  return $r;

		$exists = False;
		$r = $this->tableExists($exists); 
		if($r != OK)
			return $r . "tet";

		if (!$exists)
		{
			$query = "
			CREATE TABLE {$this->table_name} (
			timeStamp TIMESTAMP NOT NULL PRIMARY KEY)
			";
			$r = mysqli_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;
		}

		if ($operation == "set")
		{
			$query = "DELETE FROM {$this->table_name}";
			$r = mysqli_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;

			$query = "INSERT INTO {$this->table_name} (timeStamp) values (NULL)";
			$r = mysqli_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;
		}

		else if ($operation == "get")
		{
			$query = "SELECT timeStamp FROM {$this->table_name} ORDER BY timeStamp DESC LIMIT 1";
			$result = mysqli_query($this->link, $query);
			if (!$result) 
				return ERROR_QUERY;
			
			$row = mysqli_fetch_assoc($result);

			if (($n = mysqli_num_rows($result)) == 0) 
				$status = "Nunca";
			else
				$status = $row['timeStamp'];

		  	mysqli_free_result($result);
		}	
		return OK;
	}	
}
?>