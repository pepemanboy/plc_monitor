<!doctype html>
<html lang="en">
  <head>
    <!-- Meta tags requeridos -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS --> 
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">

    <!-- CSS propio -->
    <link rel = "stylesheet" href = "http://cirotec.mx/plcmonitor/css/plcshield.css">

    <!-- Titulo de pagina --> 
    <title>PLC Shield Monitor</title>
  </head>
  <body>

    <!-- Navbar --> 
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
      <a class="navbar-brand" href="#">PLC Shield Monitor</a>
      <!-- Boton de toggle en movil -->
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- Contenido de navbar -->
      <div class="collapse navbar-collapse" id="navbarCollapse">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item">
            <a class="nav-link" href="#">Administrador</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Visualizador</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="#">Programación de Arduino</a>
          </li>
        </ul>
      </div>
    </nav>
    <!-- Acaba Navbar -->

    <!-- Titulo -->
    <div class = "container programacion">

      <div class = "row">
        <h2>Programación de Arduino</h2>
      </div>
      <form>
        <div class = "row">
          <!-- Nombre de Arduino -->
          <div class="input-group">
            <!-- numero -->
            <div class="input-group-prepend">
              <span class="input-group-text" id="">Nombre de PLC:</span>
            </div>
            <!-- Nombre -->
            <input type="text" class="form-control" placeholder = "Nombre">
          </div>
        </div>

        <!-- Acaba nombre de Arduino -->

        <!-- Entradas digitales -->

        <!-- Titulo -->
        <div class = "row">
          <h3>Entradas digitales</h3>
        </div>      

        <?php
        for ($i = 1; $i <= 6; $i++) {
            echo ' 
        <!--Entrada digital -->
              <div class = "row di">
                <div class="input-group">
                  <!-- numero -->
                  <div class="input-group-prepend">
                    <span class="input-group-text" id="">#1</span>
                  </div>
                  <!-- Label nombre -->
                  <div class="input-group-prepend">
                    <span class="input-group-text" id="">Nombre:</span>
                  </div>
                  <!-- Nombre -->
                  <input type="text" class="form-control" placeholder = "Nombre">
                  <!-- Label nombre -->
                  <div class="input-group-prepend">
                    <span class="input-group-text" id="">Frecuencia de muestreo (segundos):</span>
                  </div>
                  <!-- Intervalo -->
                  <input type="number" class="form-control" placeholder = "Intervalo secs">              
                  <!-- Checkbox contador -->
                  <div class="input-group-append">
                    <div class="input-group-text">                  
                      Contador
                      <input class ="contador" type="checkbox" aria-label="Checkbox for following text input">
                    </div>
                  </div>
                </div>
              </div>          
              <!-- Acaba entrada digital -->
            ';
        }
        ?>

        <!-- Acaban entradas digitales -->

        <!-- Entradas analógicas -->

        <!-- Titulo -->
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
                    <span class="input-group-text" id="">#1</span>
                  </div>
                  <!-- Label nombre -->
                  <div class="input-group-prepend">
                    <span class="input-group-text" id="">Nombre:</span>
                  </div>
                  <!-- Nombre -->
                  <input type="text" class="form-control" placeholder = "Nombre">
                  <!-- Label nombre -->
                  <div class="input-group-prepend">
                    <span class="input-group-text" id="">Frecuencia de muestreo (segundos):</span>
                  </div>
                  <!-- Intervalo -->
                  <input type="number" class="form-control" placeholder = "Intervalo secs">              
                  <!-- Checkbox contador -->
                  <div class="input-group-append">
                    <div class="input-group-text">                  
                      Contador
                      <input class ="contador" type="checkbox" aria-label="Checkbox for following text input">
                    </div>
                  </div>
                </div>
              </div>          
              <!-- Acaba entrada analógica -->
            ';
        }
        ?>

        <!-- Acaban entradas analógicas -->

        <!-- Salidas -->

        <!-- Titulo -->
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
                    <span class="input-group-text" id="">#1</span>
                  </div>
                  <!-- Label nombre -->
                  <div class="input-group-prepend">
                    <span class="input-group-text" id="">Nombre:</span>
                  </div>
                  <!-- Nombre -->
                  <input type="text" class="form-control" placeholder = "Nombre">
                  <!-- Label nombre -->
                  <div class="input-group-prepend">
                    <span class="input-group-text" id="">Frecuencia de muestreo (segundos):</span>
                  </div>
                  <!-- Intervalo -->
                  <input type="number" class="form-control" placeholder = "Intervalo secs">              
                  <!-- Checkbox contador -->
                  <div class="input-group-append">
                    <div class="input-group-text">                  
                      Contador
                      <input class ="contador" type="checkbox" aria-label="Checkbox for following text input">
                    </div>
                  </div>
                </div>
              </div>          
              <!-- Acaba salida -->
            ';
        }
        ?>

        <!-- Acaban salidas -->


        <div class = "row boton-programar">
          <button type="button" class="btn btn-primary btn-lg btn-block">Programar</button>
        </div>
      </form>

    </div>


    <!-- JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
    <!-- Acaba Javascript -->

  </body>
</html>