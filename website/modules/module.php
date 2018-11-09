<?php
/**
 * Module implementation.
 */

include_once("definitions.php");

/**
 * Module class definitions.
 *
 * A module is defined as a database table connection, with capability of managing POST requests.
 *
 * Input POST request parameters shall be standard POST parameters.
 *
 * According to the "operation" parameter, the other parameters will be processed, unless otherwise stated by the sub-class.
 *
 * The module will echo the result of the operation in the following format:
 * * {variable1(value_of_variable_1)array_variable_2(array_element_1,array_element_2)error(error_code)}
 * * example: {name("John")age(12)scores(80,75,55)error("OK")}
 *
 * The error code will be one of the ones defined in definitions.php.
 *
 * Functions to parse the output are defined in plc_util.php.
 */
class Module
{
    /*** CLASS VARIABLES */

    /** Connection to database. */
    protected $link = null;
    /** @var boolean Module initialized flag. */
    protected $initialized = False;
    /** @var string Table name in database. */
    private $table_name = "";

    /*** METHOD PROTOYPES, TO BE DEFINED BY SUBMODULES */

    /** 
     * Initialize function, to be defined by submodule.
     *
     * If not defined, returns OK.
     *
     * @return integer Error code.
     */
    protected function initialize(){ return OK; }

    /** 
     * Post request handler to be defined by submodule.
     *
     * If not defined, returns OK.
     *
     * @param string $operation POST Request operation.
     * @param {out}string $message Output message.
     * @return integer Error code.
     */
    protected function postRequestData($operation, &$message){ return OK; }

    /** 
     * Prepare module for post request, to be defined by submodule. 
     *
     * If not defined, returns OK.
     *
     * @return integer Error code.
     */
    protected function postInitialize(){ return OK; }


    /*** CONSTRUCTOR AND DESTRUCTOR */

    /** 
     * Constructor.
     * 
     * Calls _initialize() function.
     * 
     */
    public function __construct() {
        $this->_initialize();
    }

    /** 
     * Destructor.
     * 
     * Closes database connection.
     */
    public function __destruct() {
        if($this->link)
            mysqli_close($this->link);
    }

    /*** SETTERS AND GETTERS */

    /** 
     * Check if the class has been properly initialized .
     *
     * @return True if initialized, else false.
     */
    public function initialized()
    {
        return $this->initialized;
    }

    /*** PRIVATE FUNCTIONS */

    /** 
     * Internal initialize function 
     *
     * Set initialized to false. Then, attempt to connect to database. Then, attempt initialize() function. If all succeeds, set initialized to true.
     * 
     * @return integer Error code.
     */
    private function _initialize()
    {
        $this->initialized = false;

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

    /** 
     * Get POST request parameters. 
     *
     * @param string $parameter_name Parameter to look for in POST parameters.
     * @param {out}string $parameter Placeholder to put parameter value if found.
     * @return boolean True if parameter is set, False otherwise 
     */
    protected function getPostParameter($parameter_name, &$parameter = null)
    {
        if (!isset($_POST[$parameter_name])) 
            return False;
        $parameter = $_POST[$parameter_name];
        return True;
    }

    /** 
     * Set output parameter
     *
     * Sticking to the convention described at the beginning of this class.
     * To do: change to Json
     *
     * @param string $parameter_name Variable name
     * @param string $parameter Variable value
     * @param {out}string $message Placeholder to append variable
     */
    protected function setParameter($parameter_name, $parameter, &$message)
    {
        $message .= "{$parameter_name}({$parameter})";
    }

    /** 
     * Set output array
     *
     * Sticking to the convention described at the beginning of this class.
     * To do: change to Json
     *
     * @param string $parameter_name Variable name
     * @param string $array Array
     * @param integer $array_length Array length
     * @param {out}string $message Placeholder to append array
     */
    protected function setParameterArray($parameter_name, $array, $array_length, &$message)
    {
        $p = "";
        for($i = 0; $i < $array_length; $i++)
        {
          $p .= $array[$i];
          if($i < $array_length - 1)
            $p .= ",";
        }
        $this->setParameter($parameter_name, $p, $message);
    }

    /** 
     * POST request handler
     *
     * Sticking to the convention described at the beginning of this class.
     * 
     * Checks if the request method is POST, and that the "operation" parameter is set. The operation parameter is given to the postRequestData function as an argument, and the output message is echoed.
     *
     * @return integer Error code.
     */
    public function postRequest()
    {
        if (!$this->initialized())
            return ERROR_CONNECTION;

        if ($_SERVER["REQUEST_METHOD"] != "POST")
            return ERROR_ARGUMENTS;

        if(!isset($_POST["operation"]))
            return ERROR_ARGUMENTS;

        $operation = $_POST["operation"];

        $r = $this->postInitialize();
        if ($r != OK)
            return $r;

        $message = "";
        $r = $this->postRequestData($operation, $message);
        echo("{{$message}error({$r})}");

        return $r;
    }
}

?>