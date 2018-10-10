<?php 

/** Service in a database table */
class TableService { 
    protected $table; 
    protected $database;
    protected $db_username;
    protected $db_password;
    protected $table_prefix;
    protected $operations;

    /** Link to database */
    protected $link; 

    /** Default constructor */
    public function __construct() {
        $this->table = $table;
        $this->database = DATABASE;
        $this->db_username = USERNAME;
        $this->db_password = PASSWORD;
        $this->table_prefix = TABLE_PREFIX;
    }

    /** Deinit */
    public function deinit($error_code)
    {
    	if($this->$link)
			mysqli_close($this->$link);
		$this->initialized = false;
		return $error_code;
    }

    /** Connect to database and table */
    public function init()
    {
    	// Already initialized
    	if ($this->initialized)
    		return OK;

		// Connect to server and database
		$this->$link = null;
		$r = connectToDatabase($this->$link);
		if ($r != OK)
			return deinit($r);

		// Query table existent
		$exists = False;
		$r = tableExists($this->$link, $this->$table_name, $exists); 
		if ($r != OK)
			return deinit($r);

		// Create table if it doesnt exist
		if (!$exists)
		{
			$query = createTable();
			$r = mysqli_query($this->$link,$query);
			if (!$r)
				return deinit(ERROR_QUERY);
		}

    	$this->initialized = true;
    	return OK;
    }

    /** Process operation */
    public function processOperation($operation, $args = null, &$ret = null)
    {
    	if(!in_array($operation, $operations))
    		return ERROR_ARGUMENTS;
    	$r = call_user_func($operations[$operation], $args, $ret);
    	return $r;
    }

    /** Add operation */
    private function addOperation($operation)
    {
    	array_push($operations, $operation);
    	return OK; 
    }    

    /** Return MySQL query to create table */
    public function createTable();
}

class InputsService extends TableService
{
	public function __construct()
	{
		parent::__construct();
	}

	public function post()
	{

	}

}

$inputsService = new InputsService();


if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
	$inputsService->getOperation();
	if($inputsService->error())
		_



	// Check for expected POST arguments
if (!isset($_POST['plc_number']) or !isset($_POST['operation'])) 
{
    _exit(ERROR_ARGUMENTS);
}
	$loginService->init();
	if (!isset($_POST['operation']) ) 
		_exit(ERROR_ARGUMENTS);
	processOperation()
}




$foo = new Foo; 
?> 