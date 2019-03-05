<?php
/**
 * PLC control inputs module implementation.
 */

session_start();

include_once( dirname(__FILE__) . '/module.php');
include_once( dirname(__FILE__) . '/tabla_plcs.php');

/**
 * PLC control inputs module.
 *
 * Contains functions to get and set PLC inputs from database table.
 *
 * 6 digital inputs, 6 analog inputs.
 */
class ControlInputs extends Module
{
    /**
     * Prepare module for POST request handler.
     *
     * Uses the "plc_number" POST parameter.
     *
     * Checks if PLC exists in database (findPlcById in plc_util.php). If not, returns error.
     *
     * Creates plc{$plc_number}_inputs table if it does not exist.
     * Table contains:
     * * timeStamp
     * * integer di1-di6 digital input values
     * * integer ai1-ai6 analog input values
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

		$this->table_name = "plc{$plc_number}_inputs";

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
		     	di1 int(11) NOT NULL,
				di2 int(11) NOT NULL,
				di3 int(11) NOT NULL,
				di4 int(11) NOT NULL,
				di5 int(11) NOT NULL,
				di6 int(11) NOT NULL,
				ai1 int(11) NOT NULL,
				ai2 int(11) NOT NULL,
				ai3 int(11) NOT NULL,
				ai4 int(11) NOT NULL,
				ai5 int(11) NOT NULL,
				ai6 int(11) NOT NULL	
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
			(di1,di2,di3,di4,di5,di6,ai1,ai2,ai3,ai4,ai5,ai6) 
			VALUES (0,0,0,0,0,0,0,0,0,0,0,0);
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
		    default: return ERROR_ARGUMENTS; 
	    }
	}

	/** 
	* Set inputs in table.
	*
	* Using the following POST parameters, set inputs in the inputs table:
	* * "di1" - "di6"
	* * "ai1" - "ai6"
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postSet(&$message)
	{
		$b = True;
		$di = $ai = [];
		for ($i = 1; $i <= 6; $i++)
		{
			$b = $b && $this->getPostParameter("di{$i}", $di[$i-1]);
			$b = $b && $this->getPostParameter("ai{$i}", $ai[$i-1]);
		}
		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "DELETE FROM {$this->table_name}";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		$query = "INSERT INTO {$this->table_name} (di1,di2,di3,di4,di5,di6,ai1,ai2,ai3,ai4,ai5,ai6) VALUES ({$di[0]}, {$di[1]}, {$di[2]}, {$di[3]}, {$di[4]}, {$di[5]}, {$ai[0]}, {$ai[1]}, {$ai[2]}, {$ai[3]}, {$ai[4]}, {$ai[5]})";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
	}

	/** 
	* Get inputs in table.
	*
	* If "arduino" is defined as POST Request parameter, message format: 
	* * digital_inputs(di1, ... ,di6)
	* 
	* Else, message format:
	* * digital_inputs(di1, ... ,di6)analog_inputs(ai1, ... ,ai6)
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postGet(&$message)
	{
		$query = "
		SELECT di1,di2,di3,di4,di5,di6,ai1,ai2,ai3,ai4,ai5,ai6 
		FROM {$this->table_name} ORDER BY timeStamp DESC LIMIT 1"; 

		if ($result = mysqli_query($this->link, $query)) 
		{
		    $row = mysqli_fetch_row($result); 
		    $di = array_slice($row,0,6);
		    $ai = array_slice($row,6,6);  

		    if(!$this->getPostParameter("arduino"))
		    {	
		    	$this->setJsonParameter("digital_inputs", $di);
		    	$this->setJsonParameter("analog_inputs", $ai);
		    }
		    else // Arduino
		    {
		    	$this->setJsonParameter("di", $di);
		    }
		    mysqli_free_result($result);
		}
		else
			return ERROR_QUERY;

		return OK;
	}
}
?>