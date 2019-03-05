<?php
/**
 * PLC control outputs module implementation.
 */

session_start();

include_once( dirname(__FILE__) . '/module.php');
include_once( dirname(__FILE__) . '/tabla_plcs.php');

/**
 * PLC Control outputs module.
 *
 * Contains functions to get and set PLC outputs from database table.
 *
 * 6 digital outputs.
 */
class ControlOutputs extends Module
{
    /**
     * Prepare module for POST request handler.
     *
     * Uses the "plc_number" POST parameter.
     *
     * Checks if PLC exists in database (findPlcById in plc_util.php). If not, returns error.
     *
     * Creates plc{$plc_number}_outputs table if it does not exist.
     * Table contains:
     * * timeStamp
     * * integer do1-do6 digital output values
     *
     * If table is empty, insert a row with 0 values.
     */
    protected function postInitialize()
    {
		$b = True;
		$plc_number = 0;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		$this->table_name = "plc{$plc_number}_outputs";

		$plc_table = new TablaPlcs();
		$r = $plc_table->findPlcById($plc_number);
		if ($r != OK)
		  return $r;

		$exists = False;
		$r = $this->tableExists($exists);
		if ($r != OK)
			return $r;

		if (!$exists)
		{
		     $query = "
		     CREATE TABLE {$this->table_name} (
		     timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
		     	do1 int(11) NOT NULL,
				do2 int(11) NOT NULL,
				do3 int(11) NOT NULL,
				do4 int(11) NOT NULL,
				do5 int(11) NOT NULL,
				do6 int(11) NOT NULL,
				confirmation int(1) DEFAULT 0	
			)
			";
			$r = mysqli_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;
		}

		$empty = True;
		$r = $this->tableEmpty($empty); 
		if (!$r)
			return ERROR_QUERY;

		if ($empty)
		{
			$query = "
			INSERT INTO {$this->table_name}
			(do1,do2,do3,do4,do5,do6) 
			VALUES (0,0,0,0,0,0);
			";  
			$r = mysqli_query($this->link, $query);
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
		    case "set": return $this->postSet($message);
		    case "get": return $this->postGet($message);
		    case "confirmation": return $this->postConfirmation($message);
		    default: return ERROR_ARGUMENTS; 
	    }
	}

	/** 
	 * Set outputs in table.
	 *
	 * Using the following POST parameters, set outputs in the outputs table:
	 * * "do1" - "do6"
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function postSet(&$message)
	{
		if (!$this->getPostParameter("arduino"))
		{
			$b = True;
			$arr = null;
			$b = $b && $this->getPostParameter("outputs", $arr);

			if (!$b)
				return ERROR_ARGUMENTS;

			$query = "
			DELETE FROM {$this->table_name};
			INSERT INTO {$this->table_name} 
			(do1,do2,do3,do4,do5,do6,confirmation) 
			VALUES ({$arr[0]}, {$arr[1]}, {$arr[2]}, {$arr[3]}, {$arr[4]}, {$arr[5]}, 1);
			";  	

			$r = mysqli_multi_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;

			do{} while(mysqli_more_results($this->link) && mysqli_next_result($this->link)); // flush multi queries
		}
		else // Outputs coming from arduino
		{
			$b = True;
			$do = [];
			for ($i = 1; $i <= 6; $i++)
				$b = $b && $this->getPostParameter("do{$i}", $do[$i-1]);

			if (!$b)
				return ERROR_ARGUMENTS;

			$query = "
			DELETE FROM {$this->table_name};
			INSERT INTO {$this->table_name} 
			(do1,do2,do3,do4,do5,do6) 
			VALUES ({$do[0]}, {$do[1]}, {$do[2]}, {$do[3]}, {$do[4]}, {$do[5]});
			";

			$r = mysqli_multi_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;

			do{} while(mysqli_more_results($this->link) && mysqli_next_result($this->link)); // flush multi queries
		}

		return OK;
	}

	/** 
	* Get inputs in table.
	* 
	* Message format:
	* * digital_outputs(do1, ... ,do6)
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postGet(&$message)
	{
		$query = "SELECT do1,do2,do3,do4,do5,do6  FROM {$this->table_name} ORDER BY timeStamp DESC LIMIT 1"; 

		if ($result = mysqli_query($this->link, $query)) 
		{
		    $row = mysqli_fetch_row($result);  

		    if(!$this->getPostParameter("arduino"))
		    {		    	
		    	$this->setJsonParameter("digital_outputs", $row);
		    }
		    else // Arduino
		    {
		    	$this->setJsonParameter("do", $row);
		    }		    
		    
		    mysqli_free_result($result);
		}
		else
			return ERROR_QUERY;

		// Get confirmation flag
		if (!$this->getPostParameter("arduino"))
		{
			$query = "SELECT confirmation  FROM {$this->table_name} ORDER BY timeStamp DESC LIMIT 1"; 

			if ($result = mysqli_query($this->link, $query)) 
			{
			    $row = mysqli_fetch_row($result);  
			    $this->setJsonParameter("confirmation", $row[0]);
			    mysqli_free_result($result);
			}
			else
				return ERROR_QUERY;
		}
		// Assert onfirmation flag in arduino
		else 
		{
			$query = "UPDATE {$this->table_name} SET confirmation = 0;";
			$r = mysqli_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;
			return OK;
		}

		return OK;
	}

	/** 
	* Get confirmation value
	* 
	* Message format:
	* * confirmation(1)
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postConfirmation(&$message)
	{
		$query = "SELECT confirmation  FROM {$this->table_name} ORDER BY timeStamp DESC LIMIT 1"; 

		if ($result = mysqli_query($this->link, $query)) 
		{
		    $row = mysqli_fetch_row($result);  
		    $this->setJsonParameter("confirmation", $row[0]);
		    mysqli_free_result($result);
		}
		else
			return ERROR_QUERY;

		return OK;
	}
}
?>