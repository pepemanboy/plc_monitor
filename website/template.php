<?php session_start(); ?>
<!doctype html>
<!-- options.php -->
<html lang="en">
  <head>
    <!-- Meta tags requeridos -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS --> 
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <!-- CSS propio -->
    <link rel = "stylesheet" href = "css/plcmonitor.css">
    <!-- Titulo de pagina --> 
    <title><?php echo($title); ?> - Template</title>
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
        <h2>Template</h2> 
      </div>
    </div> <!-- Acaba container -->
    <!-- Inicia JavaScript -->
    <!-- primero jQuery, despues Popper.js, despues Bootstrap, despues propio -->
    <script src ="https://code.jquery.com/jquery-3.3.1.min.js"> </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
    <script src = "js/plcmonitor_util.js"> </script>
    <!-- Acaba Javascript -->
  </body>
</html> 