/** 
Javascript para admin.php
*/

// Variables globales
g_plc = 0;
g_signals = Array();
g_ids = Array();
g_progress = 0;

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

// Boton de respaldar senales
$('#admin-respaldar-senales-boton').click(function(){
  adminStatus("Respaldando...");
  $("#admin-respaldar-senales-boton").addClass("disabled");
  // Get plc ids
  $.post("tabla_plcs.php",
    {
      operation: "get",
      format: "array",
    },
    function(data,status){
      var err = getPhpVar(data, "error").val;
      if(!plcOk(err))
        return;
      g_ids = getPhpArray(data,"ids");
      // Initialize g_signals
      g_progress = 0;
      g_signals = Array();
      for(var i = 0; i < g_ids.length; i ++)
        g_signals[i] = {plc_number: g_ids[i], ai: Array(), di: Array()};

      // Get signals
      for(var i = 0; i < g_ids.length; i ++)
      {        
        for (var j = 0; j < 6; j ++)
        {
          getSignal(i,g_ids[i],j,'ai');
          getSignal(i,g_ids[i],j,'di');
        }        
      }  
    }); 
});

function getSignal(index, plc_number, signal_number, signal_type)
{
  $.post(
  "viz_graph.php",
  {
    plc_number: plc_number,
    signal_number: signal_number+1,
    signal_type: signal_type,
    operation: "get_backup",
  },
  function(data,status){
    // console.log("Status = " + status);
    var err = getPhpVariable(data, "error"); 
    if (!plcOk(err))
      return;
    var values = getPhpArray(data, "values").map(Number);
    var dates = getPhpArray(data, "dates");
    if (signal_type == 'ai')
    {
      g_signals[index].ai[signal_number] = {dates: dates, values: values};
    }
    else
    {
      g_signals[index].di[signal_number] = {dates: dates, values: values};
    }
    // console.log("plc number " + plc_number + " signal number " + signal_number + ' signal type ' + signal_type);
    // console.log("backupfinished = " + backupFinished());
    backupFinished();
    g_progress += 1/(g_signals.length * 12) * 100;
    $("#admin-respaldar-senales-boton").text("Respaldando " + g_progress.toFixed(2) + "%");
    if(!plcOk(err))
      return;
  }); 
}

function backupFinished()
{
  for(var i = 0; i < g_signals.length; i ++)
  {
    if (g_signals[i].di.length < 6 || g_signals[i].ai.length < 6)
      return false;
  }
  downloadZip();
  $("#admin-respaldar-senales-boton").removeClass("disabled");
  return true;
}

function downloadZip()
{
  var zip = new JSZip();
  var types = ['di','ai'];
  for(var i = 0; i < g_signals.length; i ++)
  {
    var folder = zip.folder("PLC " + g_ids[i]);
    for(var j = 0; j < 6; j ++)
    {
      for(var k = 0; k < types.length; k ++)
      {
        var fn = "plc" + g_signals[i].plc_number + "_" + types[k] + (j+1) + ".csv";
        var csv;
        if (k == 0)
        {
          csv = convertArrayOfObjectsToCSV({
            data: arraysToPoints(g_signals[i].di[j].dates,g_signals[i].di[j].values)
          });
        }
        else
        {
          csv = convertArrayOfObjectsToCSV({
            data: arraysToPoints(g_signals[i].ai[j].dates,g_signals[i].ai[j].values)
          });
        }
        
        if (csv == null) continue;
        folder.file(fn, csv);
      }      
    }
  }

  adminStatus("Respaldado OK");
  $("#admin-respaldar-senales-boton").text("Respaldar senales");

  zip.generateAsync({type:"blob"})
    .then(function(content) {
        // see FileSaver.js
        var today = new Date();
        saveAs(content, "dplastico-respaldo-" + today.toLocaleString('es-MX', { timeZone: 'America/Mexico_City' }) + ".zip");
    });
}


// Boton de borrar senales
$('#admin-borrar-senales-boton').click(function(){
});

// Boton de borrar senales adentro del modal
$("#admin-borrar-senales-modal-boton").click(function(){
  alert("adentro del modal de borrar todo");
});

$( document ).ajaxError(function() {
  adminStatus("Ajax Error");
  $("#admin-respaldar-senales-boton").removeClass("disabled");
  $("#admin-respaldar-senales-boton").text("Respaldar senales");
});