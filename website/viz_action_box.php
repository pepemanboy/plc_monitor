<?php 
/**
Administrador devices
*/

// Includes
include_once("definitions.php");
include_once("connect.php");
include_once("plc_util.php");

//Check for expected POST arguments
if (empty($_POST['actions']))
  _exit(ERROR_ARGUMENTS, $link);

// Fetch arguments
$actionsString = $_POST['actions'];
$actions = json_decode($actionsString, true);

echo("table(");
for($i = 0; $i < count($actions); $i++)
{
  $action = $actions[$i];
  $input = $action["input"];
  $threshold = $action["threshold"];
  $updown = $action["updown"];
  $output = $action["output"];
  $email = $action["email"];
  $notification_interval_s = $action["notification_interval_s"];
  $action_type = $action["action_type"];
  $delay_s = $action["delay_s"];
  $index = $i + 1;
  // Echo action
  echo("
<div class = 'viz-accion card'>
  <div class='card-header'>
    Accion " . $index . "
    <button type='button' class='btn btn-danger viz-borrar-boton' data-toggle='modal' data-target='#viz-borrar-modal'>Borrar</button>
  </div>
  <div class = 'card-body'>
    <!-- Empieza primera fila -->
    <div class='input-group mb-3'>
      <!-- Label nivel -->
      <div class='input-group-prepend'>
        <label class='input-group-text'>Nivel:</label>
      </div> 
      <!-- Input nivel -->
      <input class='form-control' type='number' placeholder='Nivel' id = 'viz-action-threshold" . $index . "'>
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
      <select class='custom-select' id = 'viz-action-output" . $index . "'>
      </select>
    </div> <!-- Acaba primera fila -->
    <!-- Empieza segunda fila -->
    <div class='input-group mb-3'>
      <!-- Label email -->
      <div class='input-group-prepend'>
        <label class='input-group-text'>Email:</label>
      </div>
      <!-- Input email -->
      <input class='form-control' type='text' placeholder='name@example.com' id = 'viz-action-email" . $index . "'>
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
          <input type='radio' class = 'viz-radio' name = 'viz-action-radios" . $index . "' aria-label='Radio button for following text input' id='' data-action-type = 1>
        </div>
      </div>
      <!-- Boton de reset -->
      <div class='input-group-prepend'>
        <button class='btn btn-outline-secondary' type='button' id = 'viz-action-reset-boton" . $index . "'>Reset</button>
      </div>
      <!--Radio temporizador -->
      <div class='input-group-prepend'>
        <div class='input-group-text'>                  
          Temporizador
          <input type='radio' class = 'viz-radio' name = 'viz-action-radios" . $index . "' aria-label='Radio button for following text input' id='' data-action-type = 2>
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
          <input type='radio' class = 'viz-radio' name = 'viz-action-radios" . $index . "' data-action-type = 3>
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