<?php
if(session_status() == PHP_SESSION_NONE)
    session_start();

include("modules/user_control.php");
$userControl = new UserControl();

$userErr = $passErr = ""; 
$user = $pass = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $r = True;
  if (empty($_POST["username"])) {
    $userErr = "Usuario requerido";
    $r = False;
  } else {
    $user = test_input($_POST["username"]);
  }

  if (empty($_POST["password"])) {
    $passErr = "Contraseña requerida";
    $r = False;
  } else {
    $pass = test_input($_POST["password"]);
  }

  if ($r)
  {
    $permissions = 0;
    $r = $userControl->validateUserPass($user, $pass, $permissions);
    if ($r == OK)
    {
      $passErr = "exito";
      $userControl->logIn($user, $permissions);
      echo("<meta http-equiv='refresh' content='0; url=admin.php' />");
    }
    else if ($r == ERROR_USERPASS)
    {
      $passErr = "Usuario / contraseña incorrectos";
      $userControl->logOut();
    }
    else
    {
      $passErr = $r;
      $userControl->logOut();
    }
  }
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>

<!doctype html>
<!-- login.php -->
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
    <title>PLC Monitor - Login</title>
    <!-- Icono en pagina -->
    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
  </head>
  <body>
    <!-- Inicia container -->
    <div class = "container admin">

      <div class = "login-form-container">
        <h2>DPlastico SCADA</h2> 
        <form method="post" class="login-form" action= "<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>">
          <div class="form-group">
            <label for="login-username-input"">Usuario</label>
            <input name = "username" class="form-control" id="login-username-input" placeholder="usuario" value ="<?php echo $user;?>">
            <div class = "login-form-error"> <?php echo $userErr;?> </div>
          </div>
          <div class="form-group">
            <label for="login-password-input">Contraseña</label>
            <input name = "password" type="password" class="form-control" id="login-password-input" placeholder="contraseña" value ="<?php echo $pass;?>">
            <div class = "login-form-error"> <?php echo $passErr;?> </div>
          </div>
          <button type ="submit" id="login-submit-boton" name="submit" class="btn btn-primary">Iniciar sesión</button>
        </form>
      </div>

    </div> <!-- Acaba container -->
    <!-- Inicia JavaScript -->
    <!-- primero jQuery, despues Popper.js, despues Bootstrap, despues propio -->
    <script src ="https://code.jquery.com/jquery-3.3.1.min.js"> </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
    <script src = "js/plcmonitor_util.js"> </script>
    <script src = "js/plcmonitor_login.js"> </script>
    <!-- Acaba Javascript -->
  </body>
</html> 