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
  // Post request 
  $.post("admin_delete_plc.php",
  {
    plc_number: n
  },
  function(data,status){
    // Checar errores
    var err = getPhpVariable(data, "error");
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
  if (!txt) // Por hacer: validar texto
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
  $.post("admin_add_plc.php",
  {
    plc_name: nombre_plc
  },
  function(data,status){
    err = getPhpVariable(data, "error");
    adminStatus(err);
    if(plcOk(err))
    {
      updateTable();
      $("#admin-agregar-modal-input").val("");
    }
  });    
}

// Actualizar tabla
function updateTable()
{
  $.post("admin_devices.php",
  {},
  function(data,status){
    var err = getPhpVariable(data, "error");
    if(plcOk(err))
    {
      var table = getPhpVariable(data, "table");
      $("#admin-plc-table").html(table);
    }        
  }); 
}

// Reportar status
function adminStatus(status)
{
  $("#admin-status-indicator").text("Status: " + status);
}