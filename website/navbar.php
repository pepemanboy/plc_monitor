<!-- Navbar --> 
<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
  <a class="navbar-brand" href="#">DPlastico SCADA</a>
  <!-- Boton de toggle en movil -->
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <!-- Contenido de navbar -->
  <div class="collapse navbar-collapse" id="navbarCollapse">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item">
        <a class="nav-link" href="admin.php" id = "navbar-item-admin">Administrador</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="viz.php" id = "navbar-item-viz">Visualizador</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="actions.php" id = "navbar-item-actions">Acciones</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="control.php" id = "navbar-item-control">Control</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="config.php" id = "navbar-item-config">Configuracion</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="debug.php" id = "navbar-item-debug">Debug</a>
      </li>     
      <li class="nav-item">
        <a class="nav-link" href="detail.php" id = "navbar-item-detail">Detalle</a>
      </li>  
      <?php
      include_once("user_control.php");
      if (adminSession())
      {
        echo ("
        <li class='nav-item'>
          <a class='nav-link' href='manager.php' id = 'navbar-item-manager'>Accounts</a>
        </li>
        <li class='nav-item'>
          <a class='nav-link' href='options.php' id = 'navbar-item-options'>Options</a>
        </li>
        ");
      }
      ?>
    </ul>
    <form class="form-inline my-2 my-lg-0">
      <button type="button" class="btn btn-outline-secondary my-2 my-sm-0" id = "logout-boton">Log out</button>
    </form>
  </div>
</nav>
<!-- Acaba Navbar -->