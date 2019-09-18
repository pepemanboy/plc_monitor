<?php 
if(session_status() == PHP_SESSION_NONE)
    session_start();

include_once( dirname(__FILE__) . "/config_program.php");
include_once( dirname(__FILE__) . "/control_inputs.php");
include_once( dirname(__FILE__) . "/control_outputs.php");
include_once( dirname(__FILE__) . "/customize.php");
include_once( dirname(__FILE__) . "/reset_counter.php");
include_once( dirname(__FILE__) . "/tabla_plcs.php");
include_once( dirname(__FILE__) . "/user_control.php");
// include_once( dirname(__FILE__) . "/viz_action.php");
include_once( dirname(__FILE__) . "/viz_graph.php");

$module_name = $_POST["module"];

$module = null;

switch ($module_name) {
	case "config_program": $module = new Config(); break;
	case "control_inputs": $module = new ControlInputs(); break;
	case "control_outputs": $module = new ControlOutputs(); break;
	case "customize": $module = new Customize(); break;
	case "reset_counter": $module = new ResetCounter(); break;
	case "tabla_plcs": $module = new TablaPlcs(); break;
	case "user_control": $module = new UserControl(); break;
	// case "viz_action": $module = new Actions(); break;
	case "viz_graph": $module = new VizGraph(); break;
	default: 
		$ret = array("error" => ERROR_ARGUMENTS);
		echo json_encode($ret, JSON_NUMERIC_CHECK);
		break;
	;
}

$module->postRequest();
$module = null;
?>
