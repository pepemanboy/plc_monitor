<?php
/**
 * PLC actions module
 */

session_start();

include_once( dirname(__FILE__) . '/module.php');

/**
 * PLC actions module.
 *
 * Contains functions to get or set the actions of a PLC.
 */
class Actions extends Module
{
    /**
     * Prepare module for POST request handler.
     *
     * Uses the following POST parameters:
     * * "plc_number"
     *
     * Checks if PLC exists in database (findPlcById in plc_util.php). If not, returns error.
     *
     * Creates plc{$plc_number}_actions table if it does not exist.
     * Table contains:
     * *id int NOT NULL AUTO_INCREMENT,
	 * *input VARCHAR(10) NOT NULL,
	 * *threshold float(12,2) NOT NULL,
	 * *updown BIT NOT NULL,
	 * *output int(11) NOT NULL,
	 * *email VARCHAR(200) NOT NULL,
	 * *notification_interval_s int(11) NOT NULL,
	 * *action_type int(11) NOT NULL,
	 * *delay_s int(11) NOT NULL,
	 * *PRIMARY KEY (id))
     */
    protected function postInitialize()
    {
    	echo("pi");
		$b = True;
		$plc_number = 0;
		$b = $b && $this->getPostParameter("plc_number", $plc_number);

		if (!$b)
			return ERROR_ARGUMENTS;

		$this->table_name = "plc{$plc_number}_actions";

		$name = "";
		$r = findPlcById($this->link, $plc_number, $name);
		if ($r != OK)
		  return $r;

		$exists = False;
		$r = tableExists($this->link, $this->table_name, $exists); 
		if ($r != OK)
			return $r;

		if (!$exists)
		{
		  $query = "
		  CREATE TABLE {$this->table_name} (
		  id int NOT NULL AUTO_INCREMENT,
		  input VARCHAR(10) NOT NULL,
		  threshold float(12,2) NOT NULL,
		  updown BIT NOT NULL,
		  output int(11) NOT NULL,
		  email VARCHAR(200) NOT NULL,
		  notification_interval_s int(11) NOT NULL,
		  action_type int(11) NOT NULL,
		  delay_s int(11) NOT NULL,
		  PRIMARY KEY (id))";

		  $r = mysqli_query($this->link, $query);
		  if (!$r)
		    return ERROR_QUERY;
		}
		echo("rok ");
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
	 * * "email" | postEmail
	 */
	protected function postRequestData($operation, &$message)
	{
		echo("prd ");
		switch ($operation) 
		{
		    case "add": return $this->postAdd($message);
		    case "get": return $this->postGet($message);
		    case "delete": return $this->postDelete($message);
		    case "email": return $this->postEmail($message);
		    default: return ERROR_ARGUMENTS; 
	    }
	}

	/** 
	* Add an action.
	*
	* Using the following POST parameters, log a value to the table:
	* * "value"
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postAdd(&$message)
	{
		echo("pa ");
		$b = True;
		$input = $threshold = $updown = $output = $email = $notification_interval_s = $action_type = $delay_s = 0;
		$b = $b && $this->getPostParameter("input", $input);
		$b = $b && $this->getPostParameter("threshold", $threshold);
		$b = $b && $this->getPostParameter("updown", $updown);
		$b = $b && $this->getPostParameter("output", $output);
		$b = $b && $this->getPostParameter("email", $email);
		$b = $b && $this->getPostParameter("notification_interval_s", $notification_interval_s);
		$b = $b && $this->getPostParameter("action_type", $action_type);
		$b = $b && $this->getPostParameter("delay_s", $delay_s);

		if (!$b)
			return ERROR_ARGUMENTS;

		if($output > 0)
		{
			$query = "DELETE FROM {$this->table_name} WHERE output = {$output};";
			$r = mysqli_query($this->link, $query);
			if (!$r)
			  return ERROR_QUERY;    
		}

		$query = "
		INSERT INTO {$this->table_name} 
		(input, threshold, updown, output, email, notification_interval_s, action_type, delay_s) 
		VALUES
		('{$input}',{$threshold},{$updown},{$output},'{$email}',{$notification_interval_s},{$action_type},{$delay_s})";

		$r = mysqli_query($this->link, $query);
		if (!$r)
			return ERROR_QUERY;

		return OK;
	}

	/** 
	* Get actions from table
	*
	* Message format: 
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postGet(&$message)
	{
		// Query rows
		$query = "SELECT id,input,threshold,updown,output,email,notification_interval_s, action_type,delay_s FROM {$this->table_name} ORDER BY input DESC";

		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		if (($n = mysqli_num_rows($result)) > 0) 
		{
			$ids = array();
			$inputs = array();
			$thresholds = array();
			$updowns = array();
			$outputs = array();
			$emails = array();
			$notification_intervals_s = array();
			$action_types = array();
			$delays_s = array();
			$i = 0;
			while($row = mysqli_fetch_assoc($result)) 
			{
				$ids[$i] = $row["id"];
				$inputs[$i] = $row["input"];
				$thresholds[$i] = $row["threshold"];
				$updowns[$i] = $row["updown"];
				$outputs[$i] = $row["output"];
				$emails[$i] = $row["email"];
				$notification_intervals_s[$i] = $row["notification_interval_s"];
				$action_types[$i] = $row["action_type"];
				$delays_s[$i] = $row["delay_s"];
				$i = $i + 1;
			}    
			mysqli_free_result($result);
		}

		if(!$this->getPostParameter("arduino"))
		{
			$this->setParameterArray("emails", $emails, $n, $message);
			$this->setParameterArray("inputs", $inputs, $n, $message);
		} 

		$this->setParameterArray("ids", $ids, $n, $message);
		$this->setParameter("n", $n, $message);

		$inputs_types = $inputs_numbers = array();
		foreach ($inputs as $i)
		{
			array_push($inputs_types, (strpos($i, 'di') !== false) ? TYPE_DIGITAL : TYPE_ANALOG);
			array_push($inputs_numbers, substr($i, 2));
		}
		$this->setParameterArray("inputs_types", $inputs_types, $n, $message);
		$this->setParameterArray("inputs_numbers", $inputs_numbers, $n, $message);

		$this->setParameterArray("thresholds", $thresholds, $n, $message);
		$this->setParameterArray("updowns", $updowns, $n, $message);
		$this->setParameterArray("outputs", $outputs, $n, $message);
		$this->setParameterArray("notification_intervals_s", $notification_intervals_s, $n, $message);
		$this->setParameterArray("action_types", $action_types, $n, $message);
		$this->setParameterArray("delays_s", $delays_s, $n, $message);

		return OK;
	}

	/** 
	* Delete an action.
	*
	* Using the following POST parameters, delete an action:
	* * "delete_id"
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postDelete(&$message)
	{
		$b = True;
		$delete_id = 0;
		$b = $b && $this->getPostParameter("delete_id", $delete_id);

		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "DELETE FROM {$this->table_name} WHERE id = {$delete_id};";
		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		return OK;
	}

	/** 
	* Send email
	*
	* Using the following POST parameters, send the corresponding email:
	* * "action_id"
	* 
	* @param {out}string $message
	* @return integer Error code
	*/
	private function postEmail(&$message)
	{
		$b = True;
		$action_id = 0;
		$b = $b && $this->getPostParameter("action_id", $action_id);

		if (!$b)
			return ERROR_ARGUMENTS;

		$query = "SELECT email, input, threshold, updown FROM {$this->table_name} WHERE id = {$action_id}";
		$result = mysqli_query($this->link, $query);
		if (!$result)
			return ERROR_QUERY;

		if($row = mysqli_fetch_assoc($result))
		{
			$email = $row['email'];
			$updown = $row['updown'];
			$input = $row['input'];
			$threshold = $row['threshold'];
			$message = $subject = $header = "";
			$this->composeEmail($email, $updown, $input, $threshold, $message, $subject, $header);
			mail($email,$subject,$message,$header);
		}
		mysqli_free_result($result);

		return OK;
	}

	/**
	* Compose Email.
	*
	* To do: format in nice HTML style
	*
	* @param string $email
	* @param integer $updown
	* @param string $input
	* @param float $threshold
	* @param {out}string $message
	* @param {out}string $subject
	* @param {out}string $header
	*/
	private function composeEmail($email, $updown, $input, $threshold, &$message, &$subject, &$header)
	{
		$message = "Entrada {$input} ha pasado el threshold {$threshold}" ;
		$message .= ($updown == 0) ? "arriba" : "abajo";
		$subject = "Alerta";
		$header = "From: Alerta SCADA";
	}
}
?>