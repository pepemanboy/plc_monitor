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
  <!--  <link rel = "stylesheet" href = "http://cirotec.mx/plcmonitor/css/tempusdominus-bootstrap-4.css"> -->

  <script defer src="https://use.fontawesome.com/releases/v5.0.12/js/all.js" integrity="sha384-Voup2lBiiyZYkRto2XWqbzxHXwzcm4A5RfdfG6466bu5LqjwwrjXCMBQBLMWh7qR" crossorigin="anonymous"></script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/css/tempusdominus-bootstrap-4.min.css" />

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
          <a class="nav-link active" href="#">Administrador</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Visualizador</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Programación de Arduino</a>
        </li>
      </ul>
    </div>
  </nav>
  <!-- Acaba Navbar -->

  <!-- Titulo -->
  <div class = "container admin">

    <div class = "row">
      <h2 >Visualizador</h2>
      <!-- Dropdown senales -->
      <div class="dropdown show dropdown-visualizador-senales">
        <a class="btn btn-secondary dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Senales de entrada
        </a>

        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
          <a class="dropdown-item" href="#">Senal 1</a>
          <a class="dropdown-item" href="#">Senal 2</a>
          <a class="dropdown-item" href="#">Senal 3</a>
        </div>
      </div>
      <!-- Acaba dropdown senales -->
    </div>

    <!-- Datepicker -->
    <div class="row visualizador-dp-row">
      <div class='col'>

        <div class="form-group">
         <div class="input-group date" id="datetimepicker7" data-target-input="nearest">
          <h3 class = "visualizador-dp-label">Inicio: </h3>
          <input type="text" class="form-control datetimepicker-input" data-target="#datetimepicker7"/>
          <div class="input-group-append" data-target="#datetimepicker7" data-toggle="datetimepicker">
            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
          </div>
        </div>
      </div>
    </div>
    <div class='col'>
      <div class="form-group">
       <div class="input-group date" id="datetimepicker8" data-target-input="nearest">
         <h3 class = "visualizador-dp-label">Fin: </h3>
         <input type="text" class="form-control datetimepicker-input" data-target="#datetimepicker8"/>
         <div class="input-group-append" data-target="#datetimepicker8" data-toggle="datetimepicker">
          <div class="input-group-text"><i class="fa fa-calendar"></i></div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Acaba datepicker -->

<div class = "row">
  <div class = "col-6">
      Grafica
    </div>
  <div class = "col-6">
    Threshold, email alarm, intervalo, salidas
  </div>

</div>




</div>






<!-- JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>

<script type="text/javascript" src="http://cirotec.mx/plcmonitor/js/moment.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/js/tempusdominus-bootstrap-4.min.js"></script>


<script type="text/javascript">
  $(function () {
    $('#datetimepicker7').datetimepicker({
      useCurrent: false
    });
    $('#datetimepicker8').datetimepicker({
      useCurrent: true
    });
    $("#datetimepicker7").on("change.datetimepicker", function (e) {
      $('#datetimepicker8').datetimepicker('minDate', e.date);
    });
    $("#datetimepicker8").on("change.datetimepicker", function (e) {
      $('#datetimepicker7').datetimepicker('maxDate', e.date);
    });
  });
</script>


<!-- Acaba Javascript -->

</body>
</html>