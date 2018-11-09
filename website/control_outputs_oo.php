<?php
session_start();

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");
include_once("module.php");

class ControlOutputs extends Module
{
	// Private variables
	private $table_name = "";

    /** Initialize module */
    protected function initialize()
    {
		return OK;
    }

    /** POST initializer */
    private function postInitialize()
    {
    	// Get parameters
		$b = True;
		$plc_number = 0;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		$this->table_name = "plc{$plc_number}_outputs";

		// Check if plc exists
		$name = "";
		$r = findPlcById($this->link,$plc_number,$name);
		if ($r != OK)
		  return $r;

		// Query table existent
		$exists = False;
		$r = tableExists($this->link, $this->table_name, $exists); 
		if ($r != OK)
			return $r;

		// Create table if it doesnt exist
		if (!$exists)
		{
		     $query = "
		     CREATE TABLE {$table_name} (
		     timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
		     	do1 int(11) NOT NULL,
				do2 int(11) NOT NULL,
				do3 int(11) NOT NULL,
				do4 int(11) NOT NULL,
				do5 int(11) NOT NULL,
				do6 int(11) NOT NULL	
			)
			";
			$r = mysqli_query($this->link,$query);
			if (!$r)
				return ERROR_QUERY;
		}

		// Query table empty
		$empty = True;
		$r = tableEmpty($this->link, $this->table_name, $empty); 
		if (!$r)
			return ERROR_QUERY;

		// Insert one row if it doesnt exist
		if ($empty)
		{
			$query = "
			INSERT INTO {$table_name}
			(do1,do2,do3,do4,do5,do6) 
			VALUES (0,0,0,0,0,0);
			";  
			$r = mysqli_query($this->link,$query);
			if (!$r)
				return ERROR_QUERY;
		}

		return OK;
    }

    /** POST Request handlers */

    /** Customize post request */
	protected function postRequestData($operation, &$message)
	{
		$r = postInitialize()
		if ($r != OK)
			return $r;

		switch ($operation) 
		{
		    case "set": return $this->postSet($message);
		    case "get": return $this->postGet($message);
		    default: return ERROR_ARGUMENTS; 
	    }
	}

	/** Save configuration in table */
	private function postSet(&$message)
	{
		// Get parameters
		$b = True;
		$do = [];
		for ($i = 1; $i <= 6; $i++)
			$b = $b && $this->getPostParameter("do{$i}", $do[$i-1]);

		if (!$b)
			return ERROR_ARGUMENTS;

		// Post control outputs to table
		$query = "
		DELETE FROM {$table_name};
		INSERT INTO {$table_name} 
		(do1,do2,do3,do4,do5,do6) 
		VALUES ({$do[0]}, {$do[1]}, {$do[2]}, {$do[3]}, {$do[4]}, {$do[5]});
		";

		$r = mysqli_multi_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		do{} while(mysqli_more_results($link) && mysqli_next_result($link)); // flush multi queries

		return OK;
	}

	/** Get configuration from table */
	private postGet(&$message)
	{
		// Query outputs
		$query = "SELECT do1,do2,do3,do4,do5,do6  FROM {$table_name} ORDER BY timeStamp DESC LIMIT 1"; 
		if ($result = mysqli_query($this->link, $query)) 
		{
			// Get row
		    $row = mysqli_fetch_row($result);   

		    $p = "";
			// Output digital_outputs variable
		    for($i = 0; $i < 6; $i++)
		    {
		    	$p .= $row[$i];
		    	if ($i != 5) $p .= ",";
		    }
		    $this->setParameter("digital_outputs", $p, $message);

		    // Free result
		    mysqli_free_result($result);
		}
		else
			return ERROR_QUERY;

		return OK;
	}

	/** Private functions */
}
?>