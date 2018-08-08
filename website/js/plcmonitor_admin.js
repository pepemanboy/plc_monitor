/** 
Javascript para admin.php
*/

// Variables globales
g_plc = 0;

// Variables a cargar cuando el documento cargue
$( document ).ready(function() {
  // Menu de pagina actual activo
  $("#navbar-item-admin").addClass("active");
  $("#navbar-item-admin").attr("href", "#");
  // Actualizar tabla de plcs
  updateTable();
});

// Boton de borrar en una fila. Mostrar modal
$(document).on("click" , '.admin-borrar-boton', function(){
  var n = $(this).attr("data-plc-number");
  g_plc = n;
  $("#admin-borrar-modal-body").text("¿Estás seguro que deseas borrar el PLC " + n + "?");
});

// Borrar un PLC
function deletePlc(n){
  // Proteccion de argumento
  if(n < 1)
    return;
  adminStatus("Borrando PLC");
  // Post request 
  $.post("tabla_plcs.php",
  {
    operation: "delete",
    plc_number: n
  },
  function(data,status){
    // Checar errores
    var err = getPhpVar(data, "error").val;
    adminStatus(err);
    if(plcOk(err))
      updateTable();
  });      
}

// Boton de borrar PLC dentro de modal
$('#admin-borrar-modal-boton').click(function(){
  $('#admin-borrar-modal').modal('hide');
  deletePlc(g_plc);
});

// Boton de agregar plc dentro de modal
$('#admin-agregar-modal-boton').click(function(){
  var txt = $('#admin-agregar-modal-input').val();
  if (!txt) 
  {
    $('#admin-agregar-modal-input').addClass("is-invalid");
  }else{
    $('#admin-agregar-modal-input').removeClass("is-invalid");
    addPlc(txt);        
    $('#admin-agregar-modal').modal('hide');
    updateTable();
  }
});

// Agregar un plc 
function addPlc(nombre_plc){
  // Checar argumentos
  if(!nombre_plc)
    return;
  adminStatus("Agregando PLC");
  $.post("tabla_plcs.php",
  {
    operation: "add",
    plc_name: nombre_plc
  },
  function(data,status){
    err = getPhpVar(data, "error").val;
    adminStatus(err);
    if(!plcOk(err))
      return;
    updateTable();
    $("#admin-agregar-modal-input").val(""); // Clear modal    
  });    
}

// Actualizar tabla
function updateTable()
{
  $.post("tabla_plcs.php",
    {
      operation: "get",
      format: "table",
    },
    function(data,status){
      var err = getPhpVar(data, "error").val;
      // adminStatus(err);
      if(!plcOk(err))
        return;
      var table = getPhpVar(data, "table");
      if(table.error)
      {
        alert("table error");
        return;
      }
      $("#admin-plc-table").html(table.val);  
      var dates = getPhpArray(data, "status_");  
      var ids = getPhpArray(data,"ids_");
      for(var i = 0; i < dates.length; i ++)
      {
        var d = moment(dates[i], 'YYYY-MM-DD HH:mm:ss');
        var current = moment();
        var diff = current.diff(d);
        if (isNaN(diff) || diff > 60000)
        {
          $("#admin-status-badge-" + ids[i]).hide();
        }
      }
    }); 
}

// Prueba
function existsTest()
{
  $.post("tabla_plcs.php",
    {
      operation: "exists",
      plc_number: 2,
    },
    function(data,status){
      var err = getPhpVar(data, "error").val;
      // adminStatus(err);
      if(!plcOk(err))
        return;
      var exists = getPhpVar(data, "exists");
      if(exists.error)
        return; 
      alert("Exists = " + exists.val); 
    }); 
}

// Reportar status
function adminStatus(status)
{
  $("#admin-status-indicator").text("Status: " + status);
}

// Debug in a row
function debugText(txt)
{
  $("#debug-row").text(txt);
}