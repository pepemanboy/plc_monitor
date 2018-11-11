<?php
/**
 * Website customization module implementation.
 */
session_start();

include_once( dirname(__FILE__) . '/module.php');

/**
 * Website customization module.
 *
 * Contains functions to set and get customization options for website looks and feels.
 *
 * Right now, the options to customize are:
 * * Webpage title
 */
class Customize extends Module
{
	/*** CLASS VARIABLES */

	/** @var string Default webpage title. */
	private $default_title = "SCADA";

    /** 
     * Initialize module.
     *
     * Creates customize table in database if it does not exists. 
     * Table contains:
     * * title VARCHAR(60) NOT NULL
     * 
     * Adds default options row if table is empty.
     *
     * @return integer Error code
     */
    protected function initialize()
    {
    	$this->table_name = "plc_customize";

		$exists = False;
		$r = tableExists($this->link, $this->table_name, $exists); 
		if($r != OK)
			return $r;

		if (!$exists)
		{
		    $query = "CREATE TABLE {$this->table_name} (title VARCHAR(60) NOT NULL)";
			$r = mysqli_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;
		}

		$empty = False;
		$r = tableEmpty($this->link, $this->table_name, $empty);
		if($r != OK)
			return $r;

		if ($empty)
		{
			$query = "INSERT INTO {$this->table_name} (title) VALUES ('{$this->DEFAULT_TITLE}')";
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
	 * * "set_properties" | postSetProperties
	 * * "get_properties" | postGetProperties
	 */
	protected function postRequestData($operation, &$message)
	{
		switch ($operation) 
		{
		    case "get_properties": return $this->postGetProperties($message);
		    case "set_properties": return $this->postSetProperties($message);
		    default: return ERROR_ARGUMENTS; 
	    }
	}

	/** 
	* Get properties in table.
	* 
	* Message format:
	* * title(webpage_title)
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postGetProperties(&$message)
	{
		$title = "";
		$r = $this->getProperty("title", $title);
		if ($r != OK)
			return $r;
		
		$this->setParameter("title", $title, $message);
		return OK;
	}

	/** 
	 * Set properties in table
	 *
	 * Using the following POST parameters, set properties in properties table:
	 * * "property_title"
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function postSetProperties(&$message)
	{
		$b = True;
		$title = null;
		$b = $b && $this->getPostParameter("property_title", $title);

		if (!$b)
			return ERROR_ARGUMENTS;

		$r = $this->setProperty("title", $title);
		return $r;
	}

	/*** PUBLIC METHODS */

	/** 
	 * Get property from database table. 
	 * 
	 * @param string $property Name of property to get.
	 * @param {out}string $value Value of property.
	 * @return integer Error code.
	 */
	public function getProperty($property, &$value)
	{
		if (!$this->initialized())
			return ERROR_INITIALIZATION;

		$query = "SELECT {$property} FROM {$this->table_name} LIMIT 1";

		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		if (($n = mysqli_num_rows($result)) > 0) 
		{
			$row = mysqli_fetch_assoc($result);
			$value = $row[$property];		
			mysqli_free_result($result);
		}

		return OK;
	}

	/** 
	 * Set property in database table. 
	 * 
	 * @param string $property Name of property to set.
	 * @param string $value Value of property to set.
	 * @return integer Error code.
	 */
	public function setProperty($property, $value)
	{
		if (!$this->initialized())
			return ERROR_INITIALIZATION;

		$query = "UPDATE {$this->table_name} SET {$property} = '{$value}';";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;
		return OK;
	}

	/** 
	 * Get title 
	 *
	 * @param {out}string $title 
	 */
	public function getTitle(&$title)
	{
		return $this->getProperty("title" , $title);
	}
}

?>