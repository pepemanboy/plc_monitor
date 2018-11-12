<?php 
/**
Control devices dropdown
*/

include_once("modules/tabla_plcs.php");

$ids = array();
$names = array();

$plc_table = new TablaPlcs();
$r = $plc_table->getPlcList($ids,$names);
$plc_table = null;

if ($r == OK)
{
	for($i = 0; $i < count($ids); $i++)
	{
		$name = $names[$i];
		$id = $ids[$i];
		echo("<a class='dropdown-item dropdown-plc' data-plc-name = '" . $name . "'data-plc-number = '" . $id . "' id = 'control-plc-dropdown-" . $id . "' href='#'>PLC ID: ". $id .". Nombre: " . $name . "</a>");	
	}
}

?>

