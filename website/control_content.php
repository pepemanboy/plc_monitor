<?php
session_start();
include_once("customize.php");
$title = "";
$r = getTitle($title);
if($r != OK)
  $title = "PLC_MONITOR";
?>
<!doctype html>
<!-- control.php -->
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
  <!-- Titulo de pagina --> 
  <title><?php echo($title); ?> - Control</title>
  <!-- Icono en pagina -->
  <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
  <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body>
  <!-- Navbar -->
  <?php include "navbar.php"; ?>
  <!-- Inicia container -->
  <div class = "container admin">
    <!-- Inicia titulo y dropdown -->    
    <div class = "row float-right">        
      <h4><span class="badge badge-light" id = "status-indicator">Status: OK</span></h4>   
    </div>
    <div class = "row titulo">
      <h2>Control de dispositivo </h2>
      <!-- Dropdown senales -->
      <div class="dropdown dropdown-visualizador-senales">
        <a class="btn btn-info dropdown-toggle plc-dropdown-menu" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display='static'>
          Selecciona un PLC
        </a>
        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
          <?php include("control_devices_dropdown.php"); ?>
        </div>
      </div> <!-- Acaba dropdown senales -->
    </div> <!-- Acaba titulo y dropdown -->
    <!-- Inicia tabla -->
    <div class = "row tabla">
      <table class="table table-hover">
        <thead>
          <tr>
            <th scope="col">Tipo</th>
            <th scope="col">1</th>
            <th scope="col">2</th>
            <th scope="col">3</th>
            <th scope="col">4</th>
            <th scope="col">5</th>
            <th scope="col">6</th>
          </tr>
        </thead>
        <tbody>
          <!-- Empiezan entradas digitales -->
          <tr>
            <th scope="row">Entradas digitales / contadores</th>
            <?php
            for ($i = 1; $i <= 6; $i++) {
              echo ' 
              <td id = "di',$i,'">
              0
              </td>
              ';
            }
            ?>
          </tr> <!-- Acaban entradas digitales -->
          <!-- Empiezan entradas analogicas -->
          <tr>
            <th scope="row">Entradas analogicas</th>
            <?php
            for ($i = 1; $i <= 6; $i++) {
              echo ' 
              <td id = "ai',$i,'">
              0
              </td>
              ';
            }
            ?>
          </tr> <!-- Acaban entradas analogicas -->
          <!-- Empiezan salidas digitales -->
          <tr>
            <th scope="row">Salidas digitales</th>
            <?php
            include_once("user_control.php");
            for ($i = 1; $i <= 6; $i++) {
              $p = !validatePermissions(PERMISSIONS_OUTPUTS) ? "disabled" : "";
              echo ' 
              <td>
              <button type="button" class="btn btn-secondary button-do" aria-pressed="false" autocomplete="off" id = "do',$i,'"    ' . $p . '>OFF</button>
              </td>
              ';
            }
            ?>
          </tr> <!-- Acaban salidas digitales -->
        </tbody>
      </table>
    </div> <!-- Acaba tabla -->
    <!-- Botones de recibir / enviar -->
    <div class = "row">
      <button type="button" class="btn btn-success control-recibir-boton" data-toggle='modal' id = "control-recibir-boton">Recibir</button>
      <?php
      include_once("user_control.php");
      if(validatePermissions(PERMISSIONS_ACTIONS))
      {
        echo("
          <button type='button' class='btn btn-success control-enviar-boton' data-toggle='modal' id = 'control-enviar-boton'>Enviar</button>
        ");
      }
      ?>
      
    </div>
    <!-- Inicia debugging -->
    <div class = "row justify-content-end" >
      <p class = "float-right"><span class="badge badge-light control-status-indicator" id = "control-inputs-indicator">Input status: OK</span></p>
    </div>
    <div class = "row justify-content-end">
      <p ><span class="badge badge-light control-status-indicator" id = "control-outputs-indicator">Output status: OK</span></p>
    </div> <!-- Acaba debugging --> 
    <!-- Debug row -->
    <div id = "debug-row"></div> 
  </div> <!-- Acaba container -->
  <!-- Inicia JavaScript -->
  <!-- primero jQuery, despues Popper.js, despues Bootstrap, despues propio -->
  <script src ="https://code.jquery.com/jquery-3.3.1.min.js"> </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.19.4/moment-with-locales.min.js"></script>
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.14/moment-timezone-with-data-2012-2022.min.js"></script>
  <script src = "js/plcmonitor_util.js"> </script>
  <script src = "js/plcmonitor_control.js"> </script>
  <!-- Acaba Javascript -->
</body>
</html>