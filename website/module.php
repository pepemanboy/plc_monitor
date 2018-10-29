<?php

// Includes
include_once("definitions.php");

class Module
{
    // Private variables
    protected $link = null;
    protected $initialized = False;
    protected $error = OK;

    /*** Method prototypes, to be defined by submodules */

    /** Initialize function, to be defined by module */
    protected function initialize(){ return OK; }
    /** Private post request function to be defined by module */
    protected function _postRequest($operation, &$message){ return OK; }


    /*** Constructor and destructor */

    /** Constructor */
    function __construct() {
        $this->_initialize();
    }

    /** Destructor */
    function __destruct() {
        if($this->link)
            mysqli_close($this->link);
    }

    /*** Setters and getters */

    /** Get module error */
    public function error()
    {
        return $this->error;
    }

    /** Set module error */
    protected function setError($err)
    {
        $this->error = $err;
    }

    /** Check if the class has been properly initialized */
    public function initialized()
    {
        return $this->initialized;
    }

    /*** Private functions */

    /** Internal initialize function */
    private function _initialize()
    {
        $this->initialized = false;

        // Connect to server and database
        $this->link = null;
        $r = connectToDatabase($this->link);
        if ($r != OK)
            return $r;

        $r = $this->initialize();
        if ($r != OK)
            return $r;

        $this->initialized = true;

        return OK;
    }

    /** Public post request function */
    public function postRequest()
    {
        // Assert module initialization
        if (!$this->initialized())
            return ERROR_CONNECTION;

        if ($_SERVER["REQUEST_METHOD"] != "POST")
            return ERROR_ARGUMENTS;

        if(!isset($_POST["operation"]))
            return ERROR_ARGUMENTS;

        $operation = $_POST["operation"];

        $message = "";
        $r = $this->_postRequest($operation, $message);
        echo("{{$message}error({$r})}");

        return $r;
    }
}

?>