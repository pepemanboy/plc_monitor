<?php 
session_start();
/**
Administrador devices
*/

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");
include_once("user_control.php");

//Check for expected POST arguments
if ( !isset($_POST['number_of_actions']) )
  _exit(ERROR_ARGUMENTS, $link);

// Fetch arguments
$number_of_actions = $_POST['number_of_actions'];

echo("table(");
for($i = 0; $i < $number_of_actions; $i++)
{
  $index = $i + 1;
  if (!!isset($_POST['modal']))
    $index = 0;
  // Echo action
  echo("
<div class = 'viz-accion card'>
  <div class='card-header viz-action-header' id = 'viz-action-header" . $index . "'>
    <span id = 'viz-action-id" . $index . "'> </span>");
  if(validatePermissions(PERMISSIONS_ACTIONS))
  {
    echo("
    <button type='button' class='btn btn-danger viz-action-borrar-boton' data-toggle='modal' data-target='#viz-borrar-modal' id = 'viz-action-borrar-boton" . $index . "'>Borrar</button>");
  }  
  echo("
  </div>
  <div class = 'card-body'>
    <!-- Empieza primera fila -->
    <div class='input-group mb-3'>
      <!-- Label nivel -->
      <div class='input-group-prepend'>
        <label class='input-group-text'>Nivel:</label>
      </div> 
      <!-- Input nivel -->
      <input class='form-control viz-action-threshold' type='number' placeholder='Nivel' id = 'viz-action-threshold" . $index . "'>
      <!-- Checkbox arriba / abajo -->
      <div class='input-group-append'>
        <div class='input-group-text'>                  
          Arriba / abajo
          <input class ='viz-checkbox' type='checkbox' aria-label='Checkbox for following text input' id = 'viz-action-updown" . $index . "'>
        </div>
      </div><!-- Acaba checkbox arriba / abajo -->
      <!-- Label salida -->
      <div class='input-group-prepend'>
        <label class='input-group-text'>Salida:</label>
      </div>
      <!-- Select salida -->
      <select class='custom-select viz-action-output' id = 'viz-action-output" . $index . "'>
      </select>
    </div> <!-- Acaba primera fila -->
    <!-- Empieza segunda fila -->
    <div class='input-group mb-3'>
      <!-- Label email -->
      <div class='input-group-prepend'>
        <label class='input-group-text'>Email:</label>
      </div>
      <!-- Input email -->
      <input class='form-control viz-action-email' type='text' placeholder='name@example.com' id = 'viz-action-email" . $index . "'>
      <!-- Label intervalo de notificaciones -->
      <div class='input-group-prepend'>
        <label class='input-group-text'>Intervalo de notificaciones:</label>
      </div>
      <!-- Intervalo de notificaciones -->
      <input class='form-control' type='number' placeholder='0' id = 'viz-action-interval" . $index . "'>
      <!-- Select minutos/horas/dias -->
      <select class='custom-select' id = 'viz-action-interval-suffix" . $index . "'>
      </select>
    </div> <!-- Acaba segunda fila -->
    <!-- Empieza tercera fila -->
    <div class='input-group'>
      <!-- Radio permanente -->
      <div class='input-group-prepend'>
        <div class='input-group-text'>                  
          Permanente
          <input type='radio' class = 'viz-radio' name = 'viz-action-radios" . $index . "' aria-label='Radio button for following text input' id='' data-action-type = " . ACTION_PERMANENT . " >
        </div>
      </div>
      <!--Radio temporizador -->
      <div class='input-group-prepend'>
        <div class='input-group-text'>                  
          Temporizador
          <input type='radio' class = 'viz-radio' name = 'viz-action-radios" . $index . "' aria-label='Radio button for following text input' id='' data-action-type = " . ACTION_DELAY . ">
        </div>
      </div>
      <!-- Input temporizador -->
      <input type='text' class='form-control' aria-label='Text input with radio button' placeholder = 'Tiempo activo' id = 'viz-action-delay" . $index . "'>
      <!-- Select segundos minutos horas temporizador -->
      <select class='custom-select' id = 'viz-action-delay-suffix" . $index . "'>
      </select>
      <!-- Radio durante -->
      <div class='input-group-prepend'>
        <div class='input-group-text'>                  
          Durante
          <input type='radio' class = 'viz-radio' name = 'viz-action-radios" . $index . "' data-action-type = " . ACTION_EVENT . ">
        </div>
      </div>
    </div> <!-- Acaba tercera fila -->
  </div>
</div> <!-- Acaban acciones -->
  ");   
}
echo(")");

// Close mySQL server connection
_exit(OK, $link);
?>