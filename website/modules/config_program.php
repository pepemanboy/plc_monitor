<?php
/**
 * PLC configuration module implementation.
 */

session_start();

include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");
include_once("module.php");

/**
 * PLC configuration module.
 *
 * Contains functions to set and get PLC configuration.
 *
 * Configuration consists of settings of the following fields of a PLC:
 * * digital inputs (name, sampling frequency, counter boolean)
 * * analog inputs (name, sampling frequency, gain, offset)
 * * digital outputs (name)
 */
class Config extends Module
{

    /**
     * Prepare module for POST request handler.
     *
     * Uses the "plc_number" POST parameter.
     *
     * Checks if PLC exists in database (findPlcById in plc_util.php). If not, returns error.
     *
     * Creates plc{$plc_number}_config table if it does not exist.
     * Table contains, for every input/output i (6 di, 6 ai, 6 do):
     * * di{$i}_name VARCHAR(200) NOT NULL,
	 * * di{$i}_freq int(11) NOT NULL,
	 * * di{$i}_count BIT NOT NULL,
	 * * ai{$i}_name VARCHAR(200) NOT NULL,
	 * * ai{$i}_freq int(11) NOT NULL,
	 * * ai{$i}_gain FLOAT(5,2) NOT NULL,
	 * * ai{$i}_offs FLOAT(5,2) NOT NULL,
	 * * do{$i}_name VARCHAR(200) NOT NULL
     *
     * If table is empty, insert a row with default values values.
     *
     * If POST parameter "arduino" is set, update the PLC's status using arduinoStatus from plc_util.php
     */    
    private function postInitialize()
    {
		$b = True;
		$plc_number = 0;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		$this->table_name = "plc{$plc_number}_config";

		$name = "";
		$r = findPlcById($this->link,$plc_number,$name);
		if ($r != OK)
		  return $r;

		$exists = False;
		$r = tableExists($this->link, $this->table_name, $exists); 
		if ($r != OK)
			return $r;

		if($this->getPostParameter("arduino"))
		{
			$r = arduinoStatus($this->link, $plc_number);
			if ($r != OK)
				return $r;

			$poweron = 0;
			if ($this->getPostParameter("poweron", $poweron))
			{
				if((int)$poweron == 1)
				{
					$r = logPowerOn($this->link, $plc_number);
					if ($r != OK)
						return $r;
				}		
			}
		}

		if (!$exists)
		{
			$query = "
			CREATE TABLE {$this->table_name} (
			timeStamp TIMESTAMP NOT NULL PRIMARY KEY";
			for($i = 1; $i <= 6; $i ++)
			{
				$query .= "
				, di{$i}_name VARCHAR(200) NOT NULL,
				di{$i}_freq int(11) NOT NULL,
				di{$i}_count BIT NOT NULL,

				ai{$i}_name VARCHAR(200) NOT NULL,
				ai{$i}_freq int(11) NOT NULL,
				ai{$i}_gain FLOAT(5,2) NOT NULL,
				ai{$i}_offs FLOAT(5,2) NOT NULL,

				do{$i}_name VARCHAR(200) NOT NULL
				";
			}
			$query .= ")";
			$r = mysqli_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;
		}

		$empty = True;
		$r = tableEmpty($this->link, $this->table_name, $empty); 
		if (!$r)
			return ERROR_QUERY;
		if($empty)
		{
			$query = "INSERT INTO {$this->table_name} (";
			for($i = 1; $i <= 6; $i ++)
			{
				$query .= "
				di{i}_name, di{i}_freq, di{i}_count,
				ai{i}_name, ai{i}_freq, ai{i}_gain, ai{i}_offs,
				do{i}_name";
				if ($i != 6) 
					$query .= ",";
			} 
			$query .= ")";

			$query .= " VALUES(";
			for($i = 1; $i <= 6; $i ++)
			{
				$query .= "
				'Digital Input {i}', 0, 0, 
				'Analog Input {i}', 0, 1, 0,
				'Digital Output {i}'";
				if ($i != 6) 
					$query .= ",";
			} 
			$query .= ");";
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
	* Save configuration in table.
	*
	* Using the following POST parameters, set outputs in the outputs table:
	* * "di" (array with [name, frequency, counter])
	* * "ai" (array with [name, frequency, gain, offset])
	* * "do" (array with [name])
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postSet(&$message)
	{
		$b = True;
		$digital_inputs = null;
		$analog_inputs = null;
		$digital_outputs = null;
		$b = $b && $this->getPostParameter("di", $digital_inputs);
		$b = $b && $this->getPostParameter("ai", $analog_inputs);
		$b = $b && $this->getPostParameter("do", $digital_outputs);

		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "
		DELETE FROM {$this->table_name};
		INSERT INTO {$this->table_name} (";
		for($i = 1; $i <= 6; $i ++)
		{
			$query .= "
			di{i}_name, di{i}_freq, di{i}_count,
			ai{i}_name, ai{i}_freq, ai{i}_gain, ai{i}_offs,
			do{i}_name";
			if ($i != 6) 
				$query .= ",";
		} 
		$query .= ") ";

		$query .= "VALUES(";
		for($i = 0; $i < 6; $i ++)
		{		
			$di = $digital_inputs[$i];
			$ai = $analog_inputs[$i];
			$do = $digital_outputs[$i];

			$query .= "
			'{$di[0]}', {$di[1]}, {$di[2]},
			'{$ai[0]}', {$ai[1]}, {$ai[2]}, {$ai[3]},
			'{$do[0]}'";

			if ($i != 5) 
				$query .= ",";
		} 
		$query .= ");";

		$r = mysqli_multi_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		do{} while(mysqli_more_results($this->link) && mysqli_next_result($this->link)); // flush multi queries

		return OK;
	}

	/** 
	 * Get config from table
	 * 
	 * If "arduino" POST parameter set, message format (for every input/output):
	 * * di1(f,c)ai1(f,g,o)
	 *
	 * Else, message format (for every input/output):
	 * * di(n,f,c)ai1(n,f,g,o)do1(n)
	 *
	 * (n = name, f = frequency, c = counter, g = gain, o = offset)
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function postGet(&$message)
	{
		$query = "SELECT ";
		for($i = 1; $i <= 6; $i ++)
		{
			$query .= "
			di{i}_name, di{i}_freq, di{i}_count,
			ai{i}_name, ai{i}_freq, ai{i}_gain, ai{i}_offs,
			do{i}_name";
			if ($i != 6) 
				$query .= ",";
		} 

		$query .= " FROM {$this->table_name} ORDER BY timeStamp DESC LIMIT 1";

		if ($result = mysqli_query($this->link, $query)) 
		{
			$row = mysqli_fetch_assoc($result);  
			if ($this->getPostParameter("arduino"))
			{
				for ($i = 1; $i <= 6; $i ++)
				{
					$this->setParameter("di{$i}", "{$row['di{$i}_freq']}, {$row['di{$i}_count']}", $message);
					$this->setParameter("ai{$i}", "{$row['ai{$i}_freq']}, {$row['ai{$i}_gain']}, {$row['ai{$i}_offs']}", $message);
				}
			} 
			else
			{
				for ($i = 1; $i <= 6; $i ++)
				{
					$this->setParameter("di{$i}", "{$row['di{$i}_name']}, {$row['di{$i}_freq']}, {$row['di{$i}_count']}", $message);
					$this->setParameter("ai{$i}", "{{$row['ai{$i}_name']}, {$row['ai{$i}_freq']}, {$row['ai{$i}_gain']}, {$row['ai{$i}_offs']}", $message);
					$this->setParameter("do{$i}", "{$row['do{$i}_name']}", $message);
				}
			}		
			mysqli_free_result($result);
		}
		else
			return ERROR_QUERY;

		return OK;
	}
}
?>