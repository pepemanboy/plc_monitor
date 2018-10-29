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
			CREATE TABLE {$this->TABLE_NAME} (
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

    /** POST Request handlers */

    /** Customize post request */
	protected function postRequestData($operation, &$message)
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

    /** Add plc to table */
    private function postAdd(&$message)
    {
    	// Get parameters
		$b = True;
		$plc_name = null;
		$b = $b && $this->getPostParameter("plc_name", $plc_name);

		if (!$b)
			return ERROR_ARGUMENTS;

		// Add PLC into table
		$query = "INSERT INTO {$this->TABLE_NAME} (name) VALUES ('{$plc_name}')";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
    }

    /** Get */
    private function postGet(&$message)
    {

    	// Get parameters
		$b = True;
		$format = null;
		$b = $b && $this->getPostParameter("format", $format);

		if (!$b)
			return ERROR_ARGUMENTS;

		// Query
		$query = "SELECT id,name FROM {$this->TABLE_NAME} ORDER BY id ASC";
		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		// Get result
		$ids = array();
		$names = array();
		if (($n = mysqli_num_rows($result)) > 0) 
		{
			// Save data of each row
			$i = 0;
			while($row = mysqli_fetch_assoc($result)) 
			{
				$ids[$i] = $row["id"];
				$names[$i] = $row["name"];
				$i = $i + 1;
			}
			mysqli_free_result($result);
		}

		$status = array();
		$i = 0;
		foreach($ids as $id)
		{
			$stat = 0;
			$r = arduinoStatus($this->$link, $id , "get" , $stat);
			if ($r != OK)
				return $r;
			$status[$i] = $stat;
			$i = $i + 1;
		}

		// Print on selected format
		if($format == "table") 
		{
			$this->printTable($ids, $names, $status, $message);		
			$n = count($ids);
			$message .= "status_(";
			for($i = 0; $i < $n; $i++)
			{
				$message .= $status[$i];
				if($i < $n - 1)
					$message .= ",";
			}
			$message .= ")";
			$message .= "ids_(";
			for($i = 0; $i < $n; $i++)
			{
				$message .= $ids[$i];
				if($i < $n - 1)
					$message .= ",";
			}
			$message .= ")";
		}
		else
		{
			$this->printArrays($ids, $names, $message);
		}

		return OK;
    }

    /** Delete PLC from  table */
    private function postDelete(&$message)
	{
		// Get parameters
		$b = True;
		$plc_number = null;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		// TODO: add missing tables here
		$suffix = "plc" . $plc_number . "_";  
		$table_inputs = $suffix . "inputs";
		$table_outputs = $suffix . "outputs";
		$table_config = $suffix . "config";
		$table_actions = $suffix . "actions";
		$table_status = $suffix . "status";
		$table_reset = $suffix . "reset";
		$tables = array($table_config, $table_inputs, $table_outputs, $table_actions, $table_status, $table_reset);
		for($i = 1; $i <= 6; $i ++)
			array_push($tables, $suffix . "di". $i, $suffix . "ai". $i);

		// Delete registry from main table
		$query = "DELETE FROM plcs WHERE id IN ('{$plc_number}')";
		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		// Delete tables if they exist
		foreach ($tables as $table)
		{
			// Query table existent
			$exists = False;
			$r = tableExists($this->link, $table, $exists); 
			if($r != OK)
				return $r;

			if(!$exists)
				continue;

			// Drop table
			$query = "DROP TABLE {$table}";
			$result = mysqli_query($this->link, $query);
			if (!$result)
				return ERROR_QUERY;
		}

		return OK;
	}

	/** Check if PLC exists in table */
	private function postExists(&$message)
	{
		// Get parameters
		$b = True;
		$plc_number = null;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		// Query
		$query = "SELECT name FROM plcs WHERE id = {$plc_number}";
		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		$exists = 0;

		if (($n = mysqli_num_rows($result)) > 0) 
			$exists = 1;

		$message .= "exists({$exists})";

		return OK;
	}

	/** Megabytes occupied by database */
	private function postMegabytes(&$message)
	{
		// Query
		$query = "
		SELECT  SUM(ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024 ), 2)) AS 'SIZE IN MB'
		FROM INFORMATION_SCHEMA.TABLES
		WHERE TABLE_SCHEMA = '" . DATABASE . "'";
		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		$mb = 0;
		if (($n = mysqli_num_rows($result)) > 0) 
		{
			// Save data of each row
			$row = mysqli_fetch_assoc($result);
			$mb = $row["SIZE IN MB"];
			mysqli_free_result($result);
			$message .= "megabytes({$mb})";
		}
		else
		{
			mysqli_free_result($result);
			return ERROR_QUERY;
		}

		return OK;
	}

	/** Get last date on which a PLC responded */
	private function postDate(&$message)
	{
		// Get parameters
		$b = True;
		$plc_number = null;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		$stat = null;
		$r = arduinoStatus($this->link, $plc_number , "get" , $stat);
		if ($r != OK)
			return $r;

		$message .= "date({$stat})";

		return OK;
	}

	/** Private functions */

    /** Format output as table */
	private function printTable($ids, $names, $status, &$message)
	{
		$message .= "table(";
		for($i = 0; $i < count($ids); $i++)
		{
			$name = $names[$i];
			$id = $ids[$i];
			$stat = $status[$i];
			$message .= "<tr id = 'admin-row-{$id}'>
		      <th scope='row'>{$id}</th>
		      <td>{$name}</td>
		      <td>{$stat}<span id = 'admin-status-badge-{$id}' class='badge badge-success'>OK</span> </td>
		      ";
			include_once("user_control.php");
			if(adminSession())
			{
				$message .= "
		        <td>
		        	<button type='button' class='btn btn-danger admin-borrar-boton' data-plc-number = '{$id}' id = 'admin-borrar-boton-{$id}' data-toggle='modal' data-target='#admin-borrar-modal'>Borrar</button>
				</td>";
			}
		    $message .= "		      
		    </tr>
			";		
		}
		$message .= ")";
	}

	/** Format output as arrays */
	private function printArrays($ids, $names, &$message)
	{
		// Return values
		$n = count($ids);
		$message .= "ids(";
		for($i = 0; $i < $n; $i++)
		{
			$message .= $ids[$i];
			if($i < $n - 1)
				$message .= ",";
		}
		$message .=  ")";
		$message .= "names(";
		for($i = 0; $i < $n; $i++)
		{
			$message .= $names[$i];
			if($i < $n - 1)
				$message .=  ",";
		}
		$message .= ")";
	}
}

?>