<?php
session_start();

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");
include_once("module.php");

class TablaPlcs extends Module
{
	// Constants
	private $TABLE_NAME = "plcs";

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
			$query = "
			CREATE TABLE plcs (
			id int NOT NULL AUTO_INCREMENT,
			name VARCHAR(200) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE (name));
			";
			$r = mysqli_query($link,$query);
			if (!$r)
				return ERROR_QUERY;
		}

		return OK;
    }

    /** Add plc to table */
    private function postAdd(&$message)
    {
    	// Check for arguments
		if (!isset($_POST['plc_name'])) 
			return ERROR_ARGUMENTS;

	    // Fetch arguments
		$plc_name = $_POST['plc_name']; 

		// Add PLC into table
		$query = "INSERT INTO {$this->TABLE_NAME} (name) VALUES ('{$plc_name}')";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
    }


	/** Customize post request */
	protected function _postRequest($operation, &$message)
	{
		switch ($operation) 
		{
		    case "add": return $this->postAdd($message);
		    case "get": return $this->postGet($message);
		    case "delete": return $this->postDelete($message);
		    case "exists": return $this->postExists($message);
		    case "megabytes": return $this->postMegabytes($message);
		    case "date": return $this->postDate($message);
		    default: return ERROR_ARGUMENTS; 
	    }
	}

	/*** PUBLIC METHODS */


}

?>