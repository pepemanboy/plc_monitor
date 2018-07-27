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
      <h4><span class="badge badge-light" id = "admin-status-indicator">Status: OK</span></h4>   
    </div>
    <div class = "row titulo">
      <h2>Administrador de dispositivos</h2> 
    </div><!-- Acaba titulo e indicador --> 
    <!-- Inicia Tabla -->
    <div class = "row admin-tabla">
      <table class="table table-hover">
        <thead>
          <tr>
            <th scope="col">ID</th>
            <th scope="col">Nombre</th>
            <th scope="col">Ultima conexion</th>
            <th scope="col">Borrar</th>
          </tr>
        </thead>
        <tbody id = "admin-plc-table">
        </tbody>
      </table>
    </div> <!-- Acaba tabla -->
    <!-- Boton de agregar nuevo -->
    <div class = "row">
      <button type="button" class="btn btn-success" data-toggle='modal' data-target='#admin-agregar-modal'>Agregar PLC</button>
    </div>
    <!-- Debug row -->
    <div id = "debug-row"></div> 
    <!-- Modal borrar -->
    <div class="modal fade" id="admin-borrar-modal" tabindex="-1" role="dialog" aria-labelledby="admin-borrar-modal-titulo" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="admin-borrar-modal-titulo">Precaución</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" id = "admin-borrar-modal-body">
            ¿Estás seguro que deseas borrar el PLC?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary" id = "admin-borrar-modal-boton">Borrar</button>
          </div>
        </div>
      </div>
    </div> <!-- Acaba modal borrar -->
    <!-- Modal agregar -->
    <div class="modal fade" id="admin-agregar-modal" tabindex="-1" role="dialog" aria-labelledby="admin-agregar-modal-titulo" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="admin-agregar-modal-titulo">Agregar nuevo PLC</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" id = "admin-agregar-modal-body">    
            <!-- Inicia cuerpo modal agregar -->            
            <div class="form-group row">
              <label for="staticEmail" class="col-sm-2 col-form-label">Nombre: </label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="admin-agregar-modal-input" placeholder="Ejemplo: Máquina 1">
              </div>
            </div> <!-- Acaba cuerpo modal agregar -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn btn-primary" id = "admin-agregar-modal-boton">Agregar</button>
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
  <script src = "js/plcmonitor_util.js"> </script>
  <script src = "js/plcmonitor_admin.js"> </script>
  <!-- Acaba Javascript -->
</body>
</html> 