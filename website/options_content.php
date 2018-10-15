<?php
session_start();
include_once("customize.php");
$title = "";
$r = getTitle($title);
if($r != OK)
  $title = "PLC_MONITOR";
?>
<!doctype html>
<!-- options.php -->
<html lang="en">
  <head>
    <!-- Meta tags requeridos -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS --> 
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <!-- CSS propio -->
    <link rel = "stylesheet" href = "css/plcshield.css">
    <!-- Titulo de pagina --> 
    <title><?php echo($title); ?> - Options</title>
    <!-- Icono en pagina -->
    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
  </head>
  <body>
    <!-- Navbar -->
    <?php include "navbar.php"; ?>
    <!-- Inicia container -->
    <div class = "container admin">
      <!--Titulo e indicador -->
      <div class = "row options-title-row">
        <h2>Options</h2> 
      </div>
      <!-- Propiedad titulo -->
      <div class = "row">
        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text" id="inputGroup-sizing-default">Titulo</span>
          </div>
          <input type="text" class="form-control" id = "options-title-input">
        </div>
      </div> <!-- Acaba propiedad titulo -->
      <!-- Save button -->
      <div class = "row">
        <button type='button' class='btn btn-success' id = "options-save-boton">Guardar cambios</button>
      </div> <!-- Acaba save button -->
      <div id = "debug-row"></div>
      <!-- Modal notif -->
      <div class="modal fade" id="notif-modal" tabindex="-1" role="dialog" aria-labelledby="notif-modal-titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="notif-modal-titulo">Notificacion</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body" id = "notif-modal-body">
              Notificacion
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div> <!-- Acaba modal notif -->  
    </div> <!-- Acaba container -->
    <!-- Inicia JavaScript -->
    <!-- primero jQuery, despues Popper.js, despues Bootstrap, despues propio -->
    <script src ="https://code.jquery.com/jquery-3.3.1.min.js"> </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
    <script src = "js/plcmonitor_util.js"> </script>
    <script src = "js/plcmonitor_options.js"> </script>
    <!-- Acaba Javascript -->
  </body>
</html> 