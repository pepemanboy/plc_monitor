<?php
session_start();
include_once("modules/user_control.php");
UserControl::validateSession(); // Will redirect if fails
?>

<!doctype html>
<!-- actions.php -->
<html lang="en">
<head>
  <!-- Meta tags requeridos -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!-- Bootstrap CSS --> 
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
  <!-- CSS propio -->
  <link rel = "stylesheet" href = "css/plcmonitor.css">
  <link rel="stylesheet" href="css/font-awesome.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/css/tempusdominus-bootstrap-4.min.css" />
  <!-- Titulo de pagina --> 
  <title>PLC Monitor - Actions</title>
  <!-- Icono en pagina -->
  <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
  <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body>
  <!-- Navbar -->
  <?php include "navbar.php"; ?>
  <!-- Inicia container -->
  <div class = "container admin">
    <!--Inicia titulo y dropdown -->
    <div class = "row float-right">        
      <h4><span class="badge badge-light viz-status-indicator" id = "viz-status-indicator">Status: OK</span></h4>  
    </div>
    <div class = "row viz-title-row">
      <h2>Acciones</h2> 
      <!-- Dropdown plcs -->
      <div class="dropdown dropdown-visualizador-plcs">
        <a class="btn btn-info dropdown-toggle plc-dropdown-menu" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display="static">
          Selecciona un PLC
        </a>
        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
          <?php include("control_devices_dropdown.php"); ?>
        </div>
      </div> <!-- Acaba dropdown plcs -->
    </div> <!-- Acaba titulo y dropdown -->
    
    <!-- Boton de agregar accion -->
    <div class ='row'>
    <?php
    include_once("user_control.php");
    if(UserControl::validatePermissions(PERMISSIONS_ACTIONS))
    {
      echo("
        <div class = 'col-6'>
        <button type='button' class='btn btn-success btn-lg btn-block disabled' id = 'viz-agregar-accion-boton' data-toggle='modal' data-target='#viz-agregar-modal'>Agregar una acción</button>
        </div>

        <div class = 'col-6'>
        <button type='button' class='btn btn-success btn-lg btn-block viz-agregar-accion-boton disabled' id = 'viz-agregar-accion-boton' data-toggle='modal' data-target='#viz-agregar-modal'>Agregar una tarea programada</button>
        </div>        
      ");
    }
    ?>
    </div>        
    <!--Acciones -->    
    <div id = "viz-actions-row"> </div>
    <!-- Debug row -->
    <div id = "debug-row"></div>
    <!-- Modal borrar -->
      <div class="modal fade" id="viz-borrar-modal" tabindex="-1" role="dialog" aria-labelledby="admin-borrar-modal-titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="viz-borrar-modal-titulo">Precaución</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body" id = "viz-borrar-modal-body">
              ¿Estás seguro que deseas borrar la acción?
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary" id = "viz-borrar-modal-boton">Borrar</button>
            </div>
          </div>
        </div>
      </div> <!-- Acaba modal borrar -->
      <!-- Modal agregar -->
      <div class="modal fade" id="viz-agregar-modal" tabindex="-1" role="dialog" aria-labelledby="viz-agregar-modal-titulo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="viz-agregar-modal-titulo">Agregar nueva accion</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body" id = "viz-agregar-modal-body">    
              <!-- Inicia cuerpo modal agregar -->            
              Agregar accion
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              <button type="submit" class="btn btn-primary" id = "viz-agregar-modal-boton">Agregar</button>
            </div>
          </div>
        </div>
      </div> <!-- Acaba modal agregar -->
  </div> <!-- Acaba container -->
  <!-- Inicia JavaScript -->
  <!-- primero jQuery, despues Popper.js, despues Bootstrap, despues propio -->
  <script src ="https://code.jquery.com/jquery-3.3.1.min.js"> </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/json3/3.3.2/json3.min.js"></script>
  <script src = "js/plcmonitor_util.js"> </script>
  <script src = "js/plcmonitor_actions.js"> </script>
<!-- Acaba Javascript -->
</body>
</html> 