// Global variables
var g_plc = 0;

// On load
$( document ).ready(function() {
  // Set active menu
  $("#navbar-item-debug").addClass("active");
  $("#navbar-item-debug").attr("href", "#");
  getPowerons();
});

// Actualizar powerons
function getPowerons()
{
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
      var ids = getPhpArray(data,"ids");
      for(var i = 0; i < ids.length; i ++)
      {
        var id = ids[i];
        $("#debug-powerons").append("<div class = 'row'> <strong> PLC ID " + id + " power-on times </strong> </div> <div class = 'row' id = 'debug-powerons" + id + "'></div> ");
        _getPoweron(id);
      }     
    }); 
}

function _getPoweron(n)
{
  // Get plc ids
  $.post("tabla_poweron.php",
    {
      operation: "get",
      plc_number: n,
    },
    function(data,status){
      var err = getPhpVar(data, "error").val;
      if(!plcOk(err))
        return;
      var ids = getPhpArr(data, "powerons").val;
      var str = "";
      for(var i = 0; i < ids.length; i ++)
      {
        var id = ids[i];
        str += id;
        if (i != (ids.length - 1)) str += "<br>";
      }      
      $("#debug-powerons" + n).html(str);
    }); 
}
