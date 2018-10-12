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
        <button type="button" class="btn btn-success" data-toggle='modal' data-target='#manager-agregar-modal' id = "manager-agregar-boton">Agregar Usuario</button>
      </div>
      <!-- Acaban botones -->
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
      <!-- Modal borrar -->
      <div class="modal fade" id="manager-borrar-modal" tabindex="-1" role="dialog" aria-labelledby="manager-borrar-modal-titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="manager-borrar-modal-titulo">Precaución</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body" id = "manager-borrar-modal-body">
              ¿Estás seguro que deseas borrar el usuario?
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary" id = "manager-borrar-modal-boton">Borrar</button>
            </div>
          </div>
        </div>
      </div> <!-- Acaba modal borrar -->   
      <!-- Modal modificar -->
      <div class="modal fade" id="manager-modificar-modal" tabindex="-1" role="dialog" aria-labelledby="manager-modificar-modal-titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="manager-modificar-modal-titulo">Modificar usuario</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <!-- Empieza cuerpo modal modificar -->
            <div class="modal-body" id = "manager-modificar-modal-body">
              <!-- Empieza fila de permisos -->
              <div  class = "form-group" id = "manager-modificar-modal-permisos-row">
                <label>Permisos</label>
                <div class='input-group mb-3'>
                  <!-- Checkbox modificar salidas -->
                  <div class='input-group-prepend'>
                    <div class='input-group-text'>                  
                      Modificar salidas
                      <input type='checkbox' class = "manager-checkbox" id = 'manager-modificar-modal-salidas-checkbox'>
                    </div>
                  </div><!-- Acaba checkbox modificar salidas-->
                  <!-- Checkbox config / acciones -->
                  <div class='input-group-append'>
                    <div class='input-group-text'>                  
                      Config / acciones
                      <input type='checkbox' class = "manager-checkbox" id = 'manager-modificar-modal-acciones-checkbox'>
                    </div>
                  </div><!-- Acaba checkbox config/acciones-->
                </div> <!-- Acaba fila de permisos -->
              </div>
              <!-- Empieza fila de usuario -->
              <div class = "form-group" id = "manager-modificar-modal-usuario-row">
                <label>Usuario</label>
                <input class="form-control" id="manager-modificar-modal-usuario-input" placeholder="user">
              </div><!-- Acaba fila de usuario -->
              <!-- Empieza fila de contraseña -->
              <div class = "form-group">
                <label>Contraseña</label>
                <input class="form-control" id="manager-modificar-modal-password-input" placeholder="pass">
              </div><!-- Acaba fila de contraseña-->
            </div> <!-- Acaba cuerpo de modal modificar -->
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary" id = "manager-modificar-modal-boton">Modificar</button>
            </div>
          </div>
        </div>
      </div> <!-- Acaba modal modificar -->  
      <!-- Modal agregar -->
      <div class="modal fade" id="manager-agregar-modal" tabindex="-1" role="dialog" aria-labelledby="manager-agregar-modal-titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="manager-agregar-modal-titulo">Agregar usuario</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <!-- Empieza cuerpo modal agregar -->
            <div class="modal-body" id = "manager-agregar-modal-body">
              <!-- Empieza fila de permisos -->
              <div  class = "form-group" id = "manager-agregar-modal-permisos-row">
                <label>Permisos</label>
                <div class='input-group mb-3'>
                  <!-- Checkbox modificar salidas -->
                  <div class='input-group-prepend'>
                    <div class='input-group-text'>                  
                      Modificar salidas
                      <input type='checkbox' class = "manager-checkbox" id = 'manager-agregar-modal-salidas-checkbox'>
                    </div>
                  </div><!-- Acaba checkbox modificar salidas-->
                  <!-- Checkbox config / acciones -->
                  <div class='input-group-append'>
                    <div class='input-group-text'>                  
                      Config / acciones
                      <input type='checkbox' class = "manager-checkbox" id = 'manager-agregar-modal-acciones-checkbox'>
                    </div>
                  </div><!-- Acaba checkbox config/acciones-->
                </div> <!-- Acaba fila de permisos -->
              </div>
              <!-- Empieza fila de usuario -->
              <div class = "form-group" id = "manager-agregar-modal-usuario-row">
                <label>Usuario</label>
                <input class="form-control" id="manager-agregar-modal-usuario-input" placeholder="user">
              </div><!-- Acaba fila de usuario -->
              <!-- Empieza fila de contraseña -->
              <div class = "form-group">
                <label>Contraseña</label>
                <input class="form-control" id="manager-agregar-modal-password-input" placeholder="pass">
              </div><!-- Acaba fila de contraseña-->
            </div> <!-- Acaba cuerpo de modal agregar -->
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary" id = "manager-agregar-modal-boton">Agregar</button>
            </div>
          </div>
        </div>
      </div> <!-- Acaba modal agregar -->  
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