<!doctype html>
<!-- admin.php -->
<?php include_once("user_control.php"); validateSession(); ?>
<html lang="en">
<head>
  <!-- Meta tags requeridos -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!-- Bootstrap CSS --> 
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
  <!-- CSS propio -->
  <link rel = "stylesheet" href = "css/plcshield.css">
  <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/css/tempusdominus-bootstrap-4.min.css" />
  <!-- Titulo de pagina --> 
  <title>PLC Shield Monitor - Admin</title>
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
      <h2>Visualizador</h2> 
      <!-- Dropdown plcs -->
      <div class="dropdown dropdown-visualizador-plcs">
        <a class="btn btn-info dropdown-toggle plc-dropdown-menu" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display="static">
          Selecciona un PLC
        </a>
        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
          <?php include("control_devices_dropdown.php"); ?>
        </div>
      </div> <!-- Acaba dropdown plcs -->
      <!-- Dropdown senales -->
      <div class="dropdown dropdown-visualizador-senales">
        <a class="btn btn-info dropdown-toggle senales-dropdown-menu" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display='static'>
          Selecciona una senal
        </a>
        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
          <?php 
          for($i = 1; $i <= 6; $i++)
          {
            echo("
              <a class='dropdown-item dropdown-senales' data-signal-type = 'di' data-signal-number = '" . $i . "' id = 'viz-signal-dropdown-di" . $i . "' href='#'>
                Digital input " . $i . "
              </a>
              ");
          }

          for($i = 1; $i <= 6; $i++)
          {
            echo("
              <a class='dropdown-item dropdown-senales' data-signal-type = 'ai' data-signal-number = '" . $i . "' id = 'viz-signal-dropdown-ai" . $i . "' href='#'>
                Analog input " . $i . "
              </a>
              ");
          }
          ?>
         
        </div>
      </div> <!-- Acaba dropdown senales -->
    </div> <!-- Acaba titulo y dropdowns -->
    <!-- Inicia seleccion de fechas -->
    <div class="row visualizador-dp-row">
      <div class='col'>
        <div class="form-group">
          <div class="input-group date" id="" data-target-input="nearest">
            <h3 class = "visualizador-dp-label">Inicio: </h3>
            <input type="text" class="form-control datetimepicker-input" id="datetimepicker1" data-toggle="datetimepicker" data-target="#datetimepicker1" placeholder = "Click para escoger inicio"/>
          </div>
        </div>
      </div>
      <div class='col'>
        <div class="form-group">
          <div class="input-group date" id="" data-target-input="nearest">
            <h3 class = "visualizador-dp-label">Fin: </h3>
            <input type="text" class="form-control datetimepicker-input" id="datetimepicker2" data-toggle="datetimepicker" data-target="#datetimepicker2" placeholder = "Click para escoger fin"/>
          </div>
        </div>
      </div>
    </div><!-- Acaba seleccion de fechas -->
    <!-- Botones de control de grafica -->
    <div class = "row viz-control-grafica-row">
      <div class = "col">
        <button type="button" class="btn btn-success btn-lg btn-block disabled" id = "viz-visualizar-fechas-boton">Visualizar fechas seleccionadas</button>
      </div>
      <div class = "col">
        <button type="button" class="btn btn-success btn-lg btn-block disabled" id = "viz-csv-boton">Descargar CSV</button>
      </div>
    </div>
    <!-- Empieza grafica -->
    <div class = "row">
      <div id="chartContainer" style="height: 370px; width: 100%;"></div>
    </div> <!-- Acaba grafica -->
    <!-- Boton de agregar accion -->
    <div class ="row">
      <button type="button" class="btn btn-success btn-lg btn-block viz-agregar-accion-boton disabled" id = "viz-agregar-accion-boton" data-toggle='modal' data-target='#viz-agregar-modal'>Selecciona una señal</button>
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
  <!-- Date time picker -->
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.19.4/moment-with-locales.min.js"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.14/moment-timezone-with-data-2012-2022.min.js"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/js/tempusdominus-bootstrap-4.min.js"></script>
  <script type="text/javascript">
    window.onload = function () {
      $('#datetimepicker1').datetimepicker({
        useCurrent: false
      });
      $('#datetimepicker2').datetimepicker({
        useCurrent: true
      });
      $("#datetimepicker1").on("change.datetimepicker", function (e) {
        $('#datetimepicker2').datetimepicker('minDate', e.date);
      });
      $("#datetimepicker2").on("change.datetimepicker", function (e) {
        $('#datetimepicker1').datetimepicker('maxDate', e.date);
      });
    }
  </script>
  <script src = "js/plcmonitor_util.js"> </script>
  <script src = "js/plcmonitor_viz.js"> </script>
  <!-- Charts -->
  <!-- <script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script> -->
  <script src="https://canvasjs.com/assets/script/jquery.canvasjs.min.js"></script>
<!-- End charts -->
<!-- Acaba Javascript -->
</body>
</html> 