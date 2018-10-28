/** 
* Javascript for manager.php
*
* @author Pepe Melendez
*/

/**
* On document load. Set active navbar item, update table.
*/
$( document ).ready(function() {
  activeNavbarItem("options");
  updateProperties();
});

/** 
* Update properties
*/
function updateProperties()
{
  moduleStatus("Querying properties");
	$.post("post_customize.php",
    {
      operation: "get_properties"
    },
    function(data,status){
      var err = getPhpVar(data, "error").val;
      if(!plcOk(err))
      {
        moduleStatus("Query properties error " + err);
        return;
      }

      // Title
      var title = getPhpVar(data, "title");      
      if(title.error)
      {
        moduleStatus("Query properties error ");
        return;
      }
      moduleStatus("Query properties OK");
			$("#options-title-input").val(title.val);    
      document.title = title.val + " - Options";
    }); 	
}

$("#options-save-boton").click(function(){
	var title = $("#options-title-input").val();
	$.post("post_customize.php",
    {
      operation: "set_properties",
      property_title: title,
    },
    function(data,status){
      var err = getPhpVar(data, "error").val;
      if(!plcOk(err))
      {
      	notify("Error guardando opciones. Codigo de error = " + err);
        return;
      }
      else
      {
      	notify("Opciones guardadas exitosamente.");
      }
      updateProperties();

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

/**
* Debug
*/
function debug(text)
{
	$("#debug-row").text(text);
}

/**
* Report status of module
* @param {string} status Status of module
*/
function moduleStatus(status)
{
  $("#status-indicator").text("Status: " + status);
}