<?php
session_start();

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");
include_once("module.php");

class Customize extends Module
{
	// Constants
	private $TABLE_NAME = "plc_customize";
	private $DEFAULT_TITLE = "SCADA";

    /** Initialize module */
    protected function initialize()
    {
		// Query table existent
		$exists = False;
		$r = tableExists($this->link, $this->TABLE_NAME, $exists); 
		if($r != OK)
			return $r;

		// Create table if it doesnt exist
		if (!$exists)
		{
		    $query = "CREATE TABLE {$this->TABLE_NAME} (title VARCHAR(60) NOT NULL)";
			$r = mysqli_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;
		}

		// Query table empty
		$empty = False;
		$r = tableEmpty($this->link, $this->TABLE_NAME, $empty);
		if($r != OK)
			return $r;

		// Insert one row if it doesnt exist
		if ($empty)
		{
			$query = "INSERT INTO {$this->TABLE_NAME} (title) VALUES ('{$this->DEFAULT_TITLE}')";
			$r = mysqli_query($this->link, $query);
			if (!$r)
				return ERROR_QUERY;
		}

		return OK;
    }

	/** Post get properties */
	private function postGetProperties(&$message)
	{
		$title = "";
		$r = $this->getProperty("title", $title);
		if ($r != OK)
			return $r;
		$message .= "title({$title})";
		return OK;
	}

	/** Post set properties */
	private function postSetProperties(&$message)
	{
		if(!isset($_POST["property_title"]))
			return ERROR_ARGUMENTS;

		$title = $_POST["property_title"];
		$r = $this->setProperty("title", $title);
		return $r;
	}

	/** Customize post request */
	protected function _postRequest($operation, &$message)
	{
		switch ($operation) 
		{
		    case "get_properties": return $this->postGetProperties($message);
		    case "set_properties": return $this->postSetProperties($message);
		    default: return ERROR_ARGUMENTS; 
	    }
	}

	/*** PUBLIC METHODS */

	/** Get property from database table. */
	public function getProperty($property, &$value)
	{
		// Assert module initialization
		if (!$this->initialized())
			return ERROR_CONNECTION;

		$query = "SELECT {$property} FROM {$this->TABLE_NAME} LIMIT 1";

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

	/** Set property from database table */
	public function setProperty($property, $value)
	{
		// Assert module initialization
		if (!$this->initialized())
			return ERROR_CONNECTION;

		$query = "UPDATE {$this->TABLE_NAME} SET {$property} = '{$value}';";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;
		return OK;
	}

	/** Get title */
	public function getTitle(&$title)
	{
		return $this->getProperty("title" , $title);
	}
}

?>