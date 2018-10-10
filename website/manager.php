<!doctype html>
<!-- admin.php -->
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
    <title>PLC Shield Monitor - Manager</title>
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
      <div class = "row">
        <h2>Account manager</h2> 
      </div>
      <!-- Inicia Tabla -->
      <div class = "row manager-tabla">
        <table class="table table-hover">
          <thead>
            <tr>
              <th scope="col">Usuario</th>
              <th scope="col">Contraseña</th>
              <th scope="col">Modificar salidas</th>
              <th scope="col">Acciones / Config</th>
              <th scope="col">Modificar</th>
              <th scope="col">Borrar</th>
            </tr>
          </thead>
          <tbody id = "manager-user-table">
          </tbody>
        </table>
      </div> <!-- Acaba tabla -->
      <!-- Boton de agregar nuevo -->
      <div class = "row">
        <button type="button" class="btn btn-success" data-toggle='modal' data-target='#manager-agregar-modal'>Agregar Usuario</button>
      </div>
      <!-- Acaban botones -->
      <div id = "manager-debug-row"> <div>
    </div> <!-- Acaba container -->
    <!-- Inicia JavaScript -->
    <!-- primero jQuery, despues Popper.js, despues Bootstrap, despues propio -->
    <script src ="https://code.jquery.com/jquery-3.3.1.min.js"> </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
    <script src = "js/plcmonitor_util.js"> </script>
    <script src = "js/plcmonitor_manager.js"> </script>
    <!-- Acaba Javascript -->
  </body>
</html> 