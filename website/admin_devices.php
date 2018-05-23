<?php 
/**
Administrador devices
*/

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");


// Connect to Database
$link = null;
$r = connectToDatabase($link);
if($r != OK)
	_exit($r, $link);

// Get registered plcs
$ids = array();
$names = array();
$r = getPlcList($link, $ids, $names);
if($r != OK)
	_exit($r, $link);

echo("table(");
for($i = 0; $i < count($ids); $i++)
{
	$name = $names[$i];
	$id = $ids[$i];
	// Echo row
	echo("<tr id = 'admin-row-" . $id . "'>
      <th scope='row'>Id: ". $id ." Nombre: " . $name . "</th>
      <td>Conectado</td>
      <td>
        <button type='button' class='btn btn-danger admin-borrar-boton' data-plc-number = '" . $id . "' id = 'admin-borrar-boton-" . $id . "' data-toggle='modal' data-target='#admin-borrar-modal'>Borrar</button>
      </td>
    </tr>
	");		
}
echo(")");

// Close mySQL server connection
_exit(OK, $link);
?>