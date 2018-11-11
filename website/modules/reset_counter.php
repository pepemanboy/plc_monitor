<?php
/**
 * PLC reset counters module
 */

session_start();

include_once( dirname(__FILE__) . '/module.php');

/**
 * PLC reset counters module
 *
 * Contains functions to reset the counters in the digital inputs of the PLC to a given value
 */
class ResetCounter extends Module
{
    /**
     * Prepare module for POST request handler.
     *
     * Uses the following POST parameters:
     * * "plc_number"
     *
     * Checks if PLC exists in database (findPlcById in plc_util.php). If not, returns error.
     *
     * Creates plc{$plc_number}_reset table if it does not exist.
     * Table contains:
     * * r1 int(11) NOT NULL,
	 * * r2 int(11) NOT NULL,
	 * * r3 int(11) NOT NULL,
	 * * r4 int(11) NOT NULL,
	 * * r5 int(11) NOT NULL,
	 * * r6 int(11) NOT NULL
	 *
	 * If table is empty, adds a row with -1 in all columns
     */
    protected function postInitialize()
    {
		$b = True;
		$plc_number = 0;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		$this->table_name = "plc{$plc_number}_reset";

		$name = "";
		$r = findPlcById($this->link, $plc_number, $name);
		if ($r != OK)
		  return $r;

		$exists = False;
		$r = tableExists($this->link, $this->table_name, $exists); 
		if ($r != OK)
			return $r;

		if (!$exists)
		{
		     $query = "
		     CREATE TABLE {$this->table_name} (
		     timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
		     	r1 int(11) NOT NULL,
				r2 int(11) NOT NULL,
				r3 int(11) NOT NULL,
				r4 int(11) NOT NULL,
				r5 int(11) NOT NULL,
				r6 int(11) NOT NULL
			)
			";
			$r = mysqli_query($this->link,$query);
			if (!$r)
				return ERROR_QUERY;
		}

		$empty = False;
		$r = tableEmpty($this->link, $this->table_name, $empty);
		if($r != OK)
			return $r;

		if ($empty)
		{
			$query = "
			INSERT INTO {$this->table_name}
			(r1,r2,r3,r4,r5,r6) 
			VALUES (-1,-1,-1,-1,-1,-1);
			";  	

			$r = mysqli_query($this->link,$query);
			if (!$r)
				return ERROR_QUERY;
		}

		return OK;
    }

    /** PRIVATE FUNCTIONS */

    /**
	 * Post Request handler.
	 *
	 * When accessed through a POST request, given a POST parameter "operation", execute a given function.
	 * * operation | function
	 * * "set" | postSet
	 * * "get" | postGet
	 */
	protected function postRequestData($operation, &$message)
	{
		switch ($operation) 
		{
		    case "set": return $this->postaSet($message);
		    case "get": return $this->postGet($message);
		    default: return ERROR_ARGUMENTS; 
	    }
	}

	/** 
	* Set counters resets
	*
	* Using the following POST parameters, modify the resets table.
	* * "r1" - "r6"
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postSet(&$message)
	{
		$b = True;
		$r[];
		for ($i = 1; $i <= 6; $i++)
		{
			$b = $b && $this->getPostParameter("r{$i}", $r[$i-1]);
		}

		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "DELETE FROM {$this->table_name}";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		$query = "INSERT INTO {$this->table_name} (r1,r2,r3,r4,r5,r6) 
		VALUES ({$r[0]}, {$r[1]}, {$r[2]}, {$r[3]}, {$r[4]}, {$r[5]})";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
	}

	/** 
	* Get resets. Deletes the row to acknowledge reading.
	*
	* Message format: resets(r1, ... , r6)
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postGet(&$message)
	{
		$query = "SELECT r1,r2,r3,r4,r5,r6 FROM  {$this->table_name} ORDER BY timeStamp DESC LIMIT 1"; 
		if ($result = mysqli_query($this->link, $query)) 
		{
		    $row = mysqli_fetch_row($result);   
		    $this->setParameterArray("resets", $row, 6, $message);
		    mysqli_free_result($result);
		}
		else
			return ERROR_QUERY;

		$query = "DELETE FROM {$this->table_name}";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
	}
}
?>