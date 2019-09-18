<?php
/**
 * PLC vizualization graph module
 */

if(session_status() == PHP_SESSION_NONE)
    session_start();

include_once( dirname(__FILE__) . '/module.php');
include_once( dirname(__FILE__) . '/tabla_plcs.php');

/**
 * PLC visualization graph module.
 *
 * Contains functions to get or log multiple points in a given signal of a PLC.
 */
class VizGraph extends Module
{
    /**
     * Prepare module for POST request handler.
     *
     * Uses the following POST parameters:
     * * "plc_number"
     * * "signal_type" - (value can be either "di" or "ai")
     * * "signal_number" 
     *
     * Checks if PLC exists in database (findPlcById in plc_util.php). If not, returns error.
     *
     * Creates plc{$plc_number}_{$signal_type}{$signal_number} table if it does not exist.
     * Table contains:
     * * timeStamp TIMESTAMP NOT NULL PRIMARY KEY
     * * val float(10,2) NOT NULL
     */
    protected function postInitialize()
    {
		$b = True;
		$signal_number = $signal_type = $plc_number = 0;
		$b = $b && $this->getPostParameter("signal_number", $signal_number);
		$b = $b && $this->getPostParameter("signal_type", $signal_type);
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		$this->table_name = "plc{$plc_number}_{$signal_type}{$signal_number}";

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
			val float(10,2) NOT NULL)";

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
		    case "get_backup": return $this->postGetBackup($message);
		    default: return ERROR_ARGUMENTS; 
	    }
	}

	/** 
	* Log a signal point
	*
	* Using the following POST parameters, log a value to the table:
	* * "value"
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postSet(&$message)
	{
		$b = True;
		$val = 0;
		$b = $b && $this->getPostParameter("value", $val);

		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "INSERT INTO {$this->table_name} (val) VALUES ({$val})";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
	}

	/** 
	* Get signal from dates selected
	*
	* Uses the following POST parameters:
	* * "date_start"
	* * "date_end" 
	* * "signal_name" (only used to return the same signal name)
	*
	* Message format: values(val1, ... , valN)dates(date1, ..., dateN)name(signal_name)
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postGet(&$message)
	{
		$b = True;
		$date_start = $date_end = $signal_name =  null;
		$b = $b && $this->getPostParameter("date_start", $date_start);
		$b = $b && $this->getPostParameter("date_end", $date_end);
		$b = $b && $this->getPostParameter("signal_name", $signal_name);

		if (!$b)
			return ERROR_ARGUMENTS;

		$val_date = null;
		$r = $this->fetchValDate($date_start, $date_end, $val_date);
		if ($r != OK)
			return $r;

		$this->setJsonParameter("name", $signal_name);
		$this->setJsonParameter("signal", $val_date);

		return OK;
	}

	/** 
	* Gets backup (all the information in the table)
	*
	* Message format: values(val1, ... , valN)dates(date1, ..., dateN)
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postGetBackup(&$message)
	{
		$val_date = null;
		$r =  $this->fetchValDate(null, null, $val_date);
		if ($r != OK)
			return $r;
		$this->setJsonParameter("signal", $val_date);
		return OK;
	}

	/** 
	* Fetch values and dates from table.
	*
	* Message format: values(val1, ... , valN)dates(date1, ..., dateN)
	* 
	* @param DateTime $date_start
	* @param DateTime $date_end
	* @param {out}string $message
	* @return integer Error code
	*/
	private function fetchValDate($date_start, $date_end, &$val_date)
	{
		$condition = "";
		if (!is_null($date_start) && !is_null($date_end))
			$condition = "WHERE timeStamp >='{$date_start}' AND timeStamp < '{$date_end}'";

		$query = "SELECT * FROM {$this->table_name}	{$condition} ORDER BY timeStamp";

	   	$result = mysqli_query($this->link, $query);
	   	if (!$result)
	   		return ERROR_QUERY;

	   	$values = array();
	   	$dates = array();
	   	
	   	if (($n = mysqli_num_rows($result)) > 0) 
	   	{
		    $values = array();
		    $dates = array();
		    $i = 0;
		    while($row = mysqli_fetch_assoc($result)) 
		    {
		        $values[$i] = $row["val"];
		        $dates[$i] = $row["timeStamp"];
		        $i = $i + 1;
		    }
		    mysqli_free_result($result);
		}
		
		$val_date = array("values" => $values, "dates" => $dates);
		return OK;
	}
}
?>