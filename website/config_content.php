<?php
session_start();
?>
<!doctype html>
<!-- config.php -->
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
    <!--Titulo e indicador -->    
    <div class = "row float-right">        
      <h4><span class="badge badge-light" id = "config-status-indicator">Status: OK</span></h4>   
    </div>
    <div class = "row">
      <h2>Configuracion</h2> 
      <!-- Dropdown senales -->
      <div class="dropdown dropdown-visualizador-senales">
        <a class="btn btn-info dropdown-toggle plc-dropdown-menu" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display='static'>
          Selecciona un PLC
        </a>
        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
          <?php include("control_devices_dropdown.php"); ?>
        </div>
      </div> <!-- Acaba dropdown senales -->
    </div>
    <!-- Botones -->
    <?php
      include_once("user_control.php");
      if(validatePermissions(PERMISSIONS_ACTIONS))
      {
        echo("
          <div class = 'row'>
            <button type='button' class='btn btn-success' id = 'config-programar-boton'>Configurar PLC</button>
            <button disabled type='button' class='btn btn-warning config-reset-boton' id = 'config-programar-boton'>Set contadores</button>
          </div>
        ");
      }
    ?>    
    <!-- Inicia form de configuracion -->
    <form>
      <!-- Inician Entradas digitales -->
      <div class = "row">
        <h3>Entradas digitales</h3>
      </div>      
      <?php
      include_once("user_control.php");
      $p = !validatePermissions(PERMISSIONS_OUTPUTS) ? "disabled" : "";
      for ($i = 1; $i <= 6; $i++) {
        echo ' 
        <!--Entrada digital -->
        <div class = "row di">
        <div class="input-group">
        <!-- numero -->
        <div class="input-group-prepend">
        <span class="input-group-text" id="">#' . $i . '</span>
        </div>
        <!-- Label nombre -->
        <div class="input-group-prepend">
        <span class="input-group-text" id="">Nombre:</span>
        </div>
        <!-- Nombre -->
        <input type="text" class="form-control" placeholder = "Nombre" id = "name-di' . $i . '"' . $p . '>
        <!-- Label frecuencia -->
        <div class="input-group-prepend">
        <span class="input-group-text" id="">Muestreo (s):</span>
        </div>
        <!-- Frecuencia -->
        <input type="number" class="form-control input-group-append" placeholder = "Intervalo secs" id = "freq-di' . $i . '"' . $p . '>              
        <!-- Checkbox contador -->
        <div class="input-group-prepend">
        <div class="input-group-text">                  
        Contador
        <input class ="contador" type="checkbox" aria-label="Checkbox for following text input" id = "count-di' . $i . '"' . $p . '>
        </div>
        </div>
        ';
        if(validatePermissions(PERMISSIONS_ACTIONS))
        {
          echo('
            <input disabled type="number" class="form-control input-group-append config-reset-input" placeholder = "Set contador" id = "config-reset-input-' . $i . '"> 
          ');
        }        

        echo '
        </div>
        </div>         
        <!-- Acaba entrada digital -->
        ';
      }
      ?> <!-- Acaban entradas digitales -->
      <!-- Entradas analógicas -->
      <div class = "row">
        <h3>Entradas analógicas</h3>
      </div>     
      <?php
      for ($i = 1; $i <= 6; $i++) {
        echo ' 
        <!--Entrada analógica -->
        <div class = "row di">
        <div class="input-group">
        <!-- numero -->
        <div class="input-group-prepend">
        <span class="input-group-text" id="">#' . $i . '</span>
        </div>
        <!-- Label nombre -->
        <div class="input-group-prepend">
        <span class="input-group-text" id="">Nombre:</span>
        </div>
        <!-- Nombre -->
        <input type="text" class="form-control" placeholder = "Nombre" id = "name-ai' . $i . '"' . $p . '>
        <!-- Label freq -->
        <div class="input-group-prepend">
        <span class="input-group-text" id="">Muestreo (s):</span>
        </div>
        <!-- Intervalo -->
        <input type="number" class="form-control" placeholder = "Intervalo secs" id = "freq-ai' . $i . '"' . $p . '>   
        <!-- Label gain -->
        <div class="input-group-prepend">
        <span class="input-group-text" id="">Gain:</span>
        </div>
        <!-- Gain-->
        <input type="number" class="form-control" placeholder = "Input gain" id = "gain-ai' . $i . '"' . $p . '> 
        <!-- Label offset -->
        <div class="input-group-prepend">
        <span class="input-group-text" id="">Offset:</span>
        </div>
        <!-- Offset-->
        <input type="number" class="form-control input-group-append" placeholder = "Input offset" id = "offs-ai' . $i . '"' . $p . '>             
        </div>
        </div>          
        <!-- Acaba entrada analógica -->
        ';
      }
      ?> <!-- Acaban entradas analógicas -->
      <!-- Salidas -->
      <div class = "row">
        <h3>Salidas</h3>
      </div>     
      <?php
      for ($i = 1; $i <= 6; $i++) {
        echo ' 
        <!--Salida -->
        <div class = "row di">
        <div class="input-group">
        <!-- numero -->
        <div class="input-group-prepend">
        <span class="input-group-text" id="">#' . $i . '</span>
        </div>
        <!-- Label nombre -->
        <div class="input-group-prepend">
        <span class="input-group-text" id="">Nombre:</span>
        </div>
        <!-- Nombre -->
        <input type="text" class="form-control" placeholder = "Nombre" id = "name-do' . $i . '"' . $p . '>
        </div>
        </div>          
        <!-- Acaba salida -->
        ';
      }
      ?> <!-- Acaban salidas -->
  </form> <!-- Acaba form de config -->
  <br><br><br>
</div> <!-- Acaba container -->
<!-- Inicia JavaScript -->
<!-- primero jQuery, despues Popper.js, despues Bootstrap, despues propio -->
<script src ="https://code.jquery.com/jquery-3.3.1.min.js"> </script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
<script src = "js/plcmonitor_util.js"> </script>
<script src = "js/plcmonitor_config.js"> </script>
<!-- Acaba Javascript -->
</body>
</html> 