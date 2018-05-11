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
        <h2>Control de dispositivo</h2>
      </div>

      <!-- Tabla -->
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
            <tr>
              <th scope="row">Entradas digitales</th>
              <?php
                for ($i = 1; $i <= 6; $i++) {
                    echo ' 
                    <td>
                      FALSE
                    </td>
                    ';
                }
              ?>
            </tr>
            <tr>
              <th scope="row">Entradas analogicas</th>
              <?php
                for ($i = 1; $i <= 6; $i++) {
                    echo ' 
                    <td>
                      0
                    </td>
                    ';
                }
              ?>
            </tr>
            <tr>
              <th scope="row">Salidas digitales</th>
              <?php
                for ($i = 1; $i <= 6; $i++) {
                    echo ' 
                    <td>
                      <button type="button" class="btn btn-primary button-do" data-toggle="button" aria-pressed="false" autocomplete="off" id = "do',$i,'">OFF</button>
                    </td>
                    ';
                }
              ?>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Acaba tabla -->





    </div>


    <!-- JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src ="https://code.jquery.com/jquery-3.3.1.min.js"> </script>
    <!--
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
    <!-- Acaba Javascript -->

    <script>

    /* Constants */
    var OUTPUT_COUNT = 6;
    /* Get the value of a button (boolean) */
    function getButtonValue(n)
    {
      var name = '#do'+n;
      var val = $(name).hasClass('active') /* ? 1 : 0 */;
      return val;
    }

    /* Get button values array */
    function getButtonValueArray()
    {
      var arr = {};
      for(var i = 0; i < OUTPUT_COUNT; ++i)
      {
        var index = 'do'+i;
        arr[i] = getButtonValue(i+1);
      }
      return arr;
    }

    function saveData(){      
      var arr = getButtonValueArray();
      $.post("control_outputs.php",
        {
          outputs: arr
        },
        function(data,status){
            alert("Data: " + data + "\nStatus: " + status);
        });
    }
    // var intervalID = setInterval(saveData, 5000);

    function fetchData(){
      $.post("control_inputs.php"),
      {
        data: '0'
      },
      function(data,status){
        alert("Data: " + data + "\nStatus: " + status);
      }      
    }
    // var interval2 = setInterval(fetchData, 5000);

    /* Update button text on toggle */
    $('.button-do').click(function(){
      var a = $(this).text();
      if($(this).hasClass('active'))
        $(this).text('OFF');
      else
        $(this).text('ON');
    });
    </script>

  </body>
</html>