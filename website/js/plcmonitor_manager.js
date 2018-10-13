/** 
* Javascript for manager.php
*
* @author Pepe Melendez
*/

g_user_id = -1;

/**
* On document load. Set active navbar item, update table.
*/
$( document ).ready(function() {
  activeNavbarItem("manager");
  updateTable();
});

/**
* Update PLC html table.
*/
function updateTable()
{
  $.post("post_user_control.php",
    {
      operation: "get_user_table"
    },
    function(data,status){
      var err = getPhpVar(data, "error").val;
      var table = getPhpVar(data, "table");
      if(!plcOk(err))
        return;
      if(table.error)
        return;
      $("#manager-user-table").html(table.val);       
    }); 
}

/**
*	Modify an account button. Open modal and fill accordingly.
*/
$(document).on("click" , '.manager-modificar-boton', function(){

  g_user_id = Number($(this).attr('data-user-number'));
  var user = $("#manager-user" + g_user_id).text();
  var pass = $("#manager-pass" + g_user_id).text();
  var out = $("#manager-out" + g_user_id).text() == "no" ? false : true;
  var act = $("#manager-act" + g_user_id).text()  == "no" ? false : true;

  $("#manager-modificar-modal-titulo").text('Modificar usuario "' + user + '"');

  $("#manager-modificar-modal-salidas-checkbox").prop('checked', out);
  $("#manager-modificar-modal-acciones-checkbox").prop('checked', act);
  $("#manager-modificar-modal-usuario-input").val(user);
  $("#manager-modificar-modal-password-input").val(pass);

  if(user == "admin")
  {
  	$("#manager-modificar-modal-permisos-row").hide();
  	$("#manager-modificar-modal-usuario-row").hide();
  }
  else
  {
  	$("#manager-modificar-modal-permisos-row").show();
  	$("#manager-modificar-modal-usuario-row").show();
  }
});

/**
*	Button inside modify account modal. Send request to modify account.
*/
$("#manager-modificar-modal-boton").click(function(){
	if (g_user_id < 0)
		return;

  var user = $("#manager-modificar-modal-usuario-input").val();
  var pass = $("#manager-modificar-modal-password-input").val();

  if (user == "" || pass == "") 
	{
		alert("incorrect user or password");
		return;
	}

  var act = $("#manager-modificar-modal-acciones-checkbox").prop('checked') ? PERMISSIONS_ACTIONS : 0;
  var out = $("#manager-modificar-modal-salidas-checkbox").prop('checked') ? PERMISSIONS_OUTPUTS : 0;

  var perm = act + out;

	$("#manager-modificar-modal").modal('hide');

  $.post("post_user_control.php",
	  {
	    operation: "modify_user",
	    user_id: g_user_id,
	    username: user,
	    password: pass,
	    permissions: perm,
	  },
	  function(data,status){
	    var err = getPhpVar(data, "error").val;
	    if(!plcOk(err))
	    {
	    	notify("No se pudo modificar usuario '" + user + "'. Código de error = " + err);
	      return;   
	    }
	    else
	    {
	    	notify("Usuario '" + user + "' modificado exitosamente.");
	    	updateTable();
	    }
	  }); 
});

/**
*	Button to add account. Open add account modal.
*/
$("#manager-agregar-boton").click(function(){
	$("#manager-agregar-modal-salidas-checkbox").prop('checked', false);
  $("#manager-agregar-modal-acciones-checkbox").prop('checked', false);
  $("#manager-agregar-modal-usuario-input").val("");
  $("#manager-agregar-modal-password-input").val("");
});



/**
*	Button inside add account modal. Send request to add new user.
*/
$("#manager-agregar-modal-boton").click(function(){

  var user = $("#manager-agregar-modal-usuario-input").val();
  var pass = $("#manager-agregar-modal-password-input").val();

  if (user == "" || pass == "") 
	{
		alert("incorrect user or password");
		return;
	}

  var act = $("#manager-agregar-modal-acciones-checkbox").prop('checked') ? PERMISSIONS_ACTIONS : 0;
  var out = $("#manager-agregar-modal-salidas-checkbox").prop('checked') ? PERMISSIONS_OUTPUTS : 0;

  var perm = act + out;

	$("#manager-agregar-modal").modal('hide');

  $.post("post_user_control.php",
	  {
	    operation: "add_user",
	    username: user,
	    password: pass,
	    permissions: perm,
	  },
	  function(data,status){
	    var err = getPhpVar(data, "error").val;	  
	    if(!plcOk(err))
	    {
	    	notify("No se pudo agregar usuario '" + user + "'. Cheque que no se repita el nombre. Código de error = " + err);
	      return;   
	    }
	    else
	    {
	    	notify("Usuario '" + user + "' agregado exitosamente.");
	    	updateTable();
	    }
	  }); 
});

/**
*	Button to erase account.
*/
$(document).on("click" , '.manager-borrar-boton', function(){

  g_user_id = Number($(this).attr('data-user-number'));
  var user = $("#manager-user" + g_user_id).text();

  $("#manager-borrar-modal-titulo").text('Borrar usuario "' + user + '"');
});

/**
*	Button inside erase account modal. Send request to erase account.
*/
$("#manager-borrar-modal-boton").click(function(){
	var user = $("#manager-user" + g_user_id).text();

	$("#manager-borrar-modal").modal('hide');

  $.post("post_user_control.php",
	  {
	    operation: "remove_user",
	    user_id: g_user_id,
	  },
	  function(data,status){
	    var err = getPhpVar(data, "error").val;	    
	    if(!plcOk(err))
	    {
	    	notify("No se pudo eliminar usuario '" + user + "'. Código de error = " + err);
	      return;   
	    }
	    else
	    {
	    	notify("Usuario '" + user + "' eliminado exitosamente.");
	    	updateTable();
	    }
	  }); 
});

/**
*	Notify user through modal.
*/

function notify(text, title = "Notificación")
{
	$("#notif-modal-titulo").text(title);
	$("#notif-modal-body").text(text);
	$("#notif-modal").modal("show");
}