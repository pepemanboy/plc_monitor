/** 
* Javascript for manager.php
*
* @author Pepe Melendez
*/

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
  $.post("user_control.php",
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