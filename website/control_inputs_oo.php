<?php
session_start();

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");
include_once("module.php");

class ControlInputs extends Module
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

		$this->table_name = "plc{$plc_number}_inputs";

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
		     CREATE TABLE " . $table_name . " (
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
			(di1,di2,di3,di4,di5,di6,ai1,ai2,ai3,ai4,ai5,ai6) 
			VALUES (0,0,0,0,0,0,0,0,0,0,0,0);
			";  	

			$r = mysqli_query($link,$query);
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
		$di = $ai = [];
		for ($i = 1; $i <= 6; $i++)
		{
			$b = $b && $this->getPostParameter("di{$i}", $di[$i-1]);
			$b = $b && $this->getPostParameter("ai{$i}", $ai[$i-1]);
		}
		if (!$b)
			return ERROR_ARGUMENTS;

		// Delete
		$query = "DELETE FROM {$table_name}";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		// Insert
		$query = "INSERT INTO {$table_name} (di1,di2,di3,di4,di5,di6,ai1,ai2,ai3,ai4,ai5,ai6) VALUES ({$di[0]}, {$di[1]}, {$di[2]}, {$di[3]}, {$di[4]}, {$di[5]}, {$ai[0]}, {$ai[1]}, {$ai[2]}, {$ai[3]}, {$ai[4]}, {$ai[5]})";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
	}

	/** Get configuration from table */
	private postGet(&$message)
	{
		// Query inputs
		$query = "SELECT di1,di2,di3,di4,di5,di6,ai1,ai2,ai3,ai4,ai5,ai6 FROM { $table_name} ORDER BY timeStamp DESC LIMIT 1"; 
		if ($result = mysqli_query($this->link, $query)) 
		{
			// Get row
		    $row = mysqli_fetch_row($result);   

		    if(!$this->getPostParameter("arduino"))
		    {
		    	// Output digital_inputs variable
		    	$p = "";
			    for($i = 0; $i < 6; $i++)
			    {
			    	$p .= $row[$i];
			    	if ($i != 5) echo (",");
			    }
			    $this->setParameter("digital_inputs", $p, $message);

			    // Output analog_inputs variable
			    $p = ""; 
			    for($i = 6; $i < 12; $i++)
			    {
			    	$p .= $row[$i]);
			    	if ($i != 11) $p .= ",";
			    }
			    $this->setParameter("analog_inputs", $p, $message);
		    }
		    else // Arduino
		    {
		    	$p = "";
		    	for($i = 0; $i < 6; $i++)
			    {
			    	$p .= $row[$i]);
			    	if ($i != 5) $p .= ",";
			    }
			    $this->setParameter("di", $p, $message);
		    }
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