<?php
session_start();

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");
include_once("module.php");

class Config extends Module
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

		$this->table_name = "plc{$plc_number}_config";

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

		// Update arduino time
		if($this->getPostParameter("arduino"))
		{
			$r = arduinoStatus($this->link, $plc_number);
			if ($r != OK)
				return $r;

			// Get resets
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

		// Create table if it doesnt exist
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

		// Query table empty
		$empty = True;
		$r = tableEmpty($this->link, $this->table_name, $empty); 
		if (!$r)
			return ERROR_QUERY;
		if($empty)
		{
			// Insert first row
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

			// Values
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

		// Values
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

	/** Get configuration from table */
	private postGet(&$message)
	{
		// Query inputs
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
			// Get row
			$row = mysqli_fetch_assoc($result);  
			if ($this->getPostParameter("arduino"))
			{
				for ($i = 1; $i <= 6; $i ++)
				{
					$this->setParameter("di{$i}", "{$row['di{$i}_freq']}, {$row['di{$i}_count']}" $message);
					$this->setParameter("ai{$i}", "{$row['ai{$i}_freq']}, {$row['ai{$i}_gain']}, {$row['ai{$i}_offs']}" $message);
				}
			} 
			else
			{
				for ($i = 1; $i <= 6; $i ++)
				{
					$this->setParameter("di{$i}", "{$row['di{$i}_name']}, {$row['di{$i}_freq']}, {$row['di{$i}_count']}" $message);
					$this->setParameter("ai{$i}", "{{$row['ai{$i}_name']}, $row['ai{$i}_freq']}, {$row['ai{$i}_gain']}, {$row['ai{$i}_offs']}" $message);
					$this->setParameter("do{$i}", "{$row['do{$i}_name']}" $message);
				}
			}		
		    // Free result
			mysqli_free_result($result);
		}
		else
		{
			return ERROR_QUERY;
		}

		return OK;
	}

	/** Private functions */

}
?>