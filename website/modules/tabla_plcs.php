<?php
/**
 * PLC main table.
 */

session_start();

include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");
include_once("module.php");

/**
 * PLC registration table module.
 *
 * Contains functions to get, register, and unregister PLCs from system.
 */
class TablaPlcs extends Module
{
    /** 
     * Initialize module.
     *
     * Creates plcs table in database if it does not exists. 
     * Table contains:
     * * id int NOT NULL AUTO_INCREMENT PRIMARY KEY
     * * name VARCHAR(200) NOT NULL UNIQUE
     *
     * @return integer Error code
     */
    protected function initialize()
    {
    	$this->table_name = "plcs";
		$exists = False;
		$r = tableExists($this->link, $this->TABLE_NAME, $exists); 
		if($r != OK)
			return $r;

		if (!$exists)
		{
			$query = "
			CREATE TABLE {$this->TABLE_NAME} (
			id int NOT NULL AUTO_INCREMENT,
			name VARCHAR(200) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE (name));
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
	 * * "add" | postAdd
	 * * "get" | postGet
	 * * "delete" | postDelete
	 * * "exists" | postExists
	 * * "megabytes" | postMegabytes
	 * * "date" | postDate
	 */
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

    /** 
	* Add plc to table
	*
	* Using the following POST parameters, add the corresponding PLC to the table:
	* * "plc_name" 
	*
	* The new plc (with a unique name), will be asigned an autoincremented ID and be added to the table.
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
    private function postAdd(&$message)
    {
		$b = True;
		$plc_name = null;
		$b = $b && $this->getPostParameter("plc_name", $plc_name);

		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "INSERT INTO {$this->TABLE_NAME} (name) VALUES ('{$plc_name}')";
		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
    }

    /** 
	* Get plcs table.
	*
	* Queries table to fetch ids and names of registered PLCs.
	*
	* If the POST parameter "format" is set to "table", the message format will be:
	* * table(html-formatted-table)status\_(plc1\_date, ... , plcn\_date)ids\_(id1, ... , idn)
	*
	* Else, the message format will be:
	* * ids(id1, ... , idn)names(name1, ... , namen)
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
    private function postGet(&$message)
    {
		$b = True;
		$format = null;
		$b = $b && $this->getPostParameter("format", $format);

		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "SELECT id,name FROM {$this->TABLE_NAME} ORDER BY id ASC";
		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		$ids = array();
		$names = array();
		if (($n = mysqli_num_rows($result)) > 0) 
		{
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
			$r = arduinoStatus($this->link, $id , "get" , $stat);
			if ($r != OK)
				return $r;
			$status[$i] = $stat;
			$i = $i + 1;
		}

		if($format == "table") 
		{
			$this->printTable($ids, $names, $status, $message);	
			$n = count($ids);

			$p = "";
			for($i = 0; $i < $n; $i++)
			{
				$p .= $status[$i];
				if($i < $n - 1)
					$p .= ",";
			}
			$this->setParameter("status_", $p, $message);

			$p = "";
			for($i = 0; $i < $n; $i++)
			{
				$p .= $ids[$i];
				if($i < $n - 1)
					$p .= ",";
			}
			$this->setParameter("ids_", $p, $message);
		}
		else
		{
			$n = count($ids);
			$this->setParameterArray("ids", $ids, $n, $message);
			$this->setParameterArray("names", $names, $n, $message);
		}

		return OK;
    }

    /** 
	 * Delete PLC from table
	 *
	 * According to the POST parameter "plc_number", will look for a match of the plc_number, and delete the row.
	 *
	 * Will also delete all the tables that correspond to that PLC.
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
    private function postDelete(&$message)
	{
		$b = True;
		$plc_number = null;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		/** @todo: add missing tables here */
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

		$query = "DELETE FROM {$this->table_name} WHERE id IN ('{$plc_number}')";
		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		foreach ($tables as $table)
		{
			$exists = False;
			$r = tableExists($this->link, $table, $exists); 
			if($r != OK)
				return $r;

			if(!$exists)
				continue;

			$query = "DROP TABLE {$table}";
			$result = mysqli_query($this->link, $query);
			if (!$result)
				return ERROR_QUERY;
		}

		return OK;
	}

	/** 
	 * Check if PLC exists in table
	 *
	 * According to the POST parameter "plc_number", will look for a match of the plc_number.
	 *
	 * Message format: exists(1) if PLC exists, else exists(0)
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function postExists(&$message)
	{
		$b = True;
		$plc_number = null;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "SELECT name FROM {$this->TABLE_NAME} WHERE id = {$plc_number}";
		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		$exists = 0;

		if (($n = mysqli_num_rows($result)) > 0) 
			$exists = 1;

		$this->setParameter("exists", $exists, $message);

		return OK;
	}

	/** 
	 * Get megabytes occupied by the database
	 *
	 * Message format: megabytes(mb_occupied_by_db)
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function postMegabytes(&$message)
	{
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

	/** 
	 * Get the last date on which a PLC reported its status.
	 *
	 * According to the POST parameter "plc_number", will look for a match of the plc_number.
	 *
	 * Message format: date(moment_js_datettime)
	 * 
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function postDate(&$message)
	{
		$b = True;
		$plc_number = null;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		$stat = null;
		$r = arduinoStatus($this->link, $plc_number , "get" , $stat);
		if ($r != OK)
			return $r;

		$this->setParameter("date", $stat, $message);

		return OK;
	}

    /** 
	 * Print PLC table
	 * 
	 * Message format: table(html_formatted_table)
	 * 
	 * @param array $ids PLC numbers
	 * @param array $names PLC names
	 * @param array $status PLC statuses
	 * @param {out}string $message
	 * @return integer Error code
	 */
	private function printTable($ids, $names, $status, &$message)
	{
		$p = "";
		for($i = 0; $i < count($ids); $i++)
		{
			$name = $names[$i];
			$id = $ids[$i];
			$stat = $status[$i];
			$p .= "<tr id = 'admin-row-{$id}'>
		      <th scope='row'>{$id}</th>
		      <td>{$name}</td>
		      <td>{$stat} <span id = 'admin-status-badge-{$id}' class='badge badge-success'>OK</span> </td>
		      ";
			include_once("user_control.php");
			if(adminSession())
			{
				$p .= "
		        <td>
		        	<button type='button' class='btn btn-danger admin-borrar-boton' data-plc-number = '{$id}' id = 'admin-borrar-boton-{$id}' data-toggle='modal' data-target='#admin-borrar-modal'>Borrar</button>
				</td>";
			}
		    $p .= "		      
		    </tr>
			";		
		}
		$this->setParameter("table", $p, $message);
	}
}

?>