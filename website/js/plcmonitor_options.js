/** 
 * Javascript for options.php
 *
 * @author Pepe Melendez
 */

/*** EVENT FUNCTIONS */

/**
 * Document. On load.
 *
 * Set webpage title, active navbar item, update table.
 */
$(document).ready(function() {
  setTitle("Options");
  activeNavbarItem("options");
  updateProperties();
});

/**
 * Save options button. On click.
 *
 * Save properties on db table.
 */
$("#options-save-boton").click(function() {
  var title = $("#options-title-input").val();
  $.post("modules/post.php", {
      module: "customize",
      operation: "set_properties",
      property_title: title,
    },
    function(data, status) {
      var err = getPhpVar(data, "error").val;
      if (!plcOk(err)) {
        notify("Error guardando opciones. Codigo de error = " + err);
        return;
      } else {
        notify("Opciones guardadas exitosamente.");
      }

      updateProperties();

    });
});

/*** CUSTOM FUNCTIONS */

/** 
 * Get properties from db table and show them on table.
 */
function updateProperties() {
  moduleStatus("Querying properties");
  $.post("modules/post.php", {
      module: "customize",
      operation: "get_properties"
    },
    function(data, status) {
      var err = getPhpVar(data, "error").val;
      if (!plcOk(err)) {
        moduleStatus("Query properties error " + err);
        return;
      }

      var title = getPhpVar(data, "title");
      if (title.error) {
        moduleStatus("Query properties error ");
        return;
      }
      moduleStatus("Query properties OK");
      $("#options-title-input").val(title.val);
      document.title = title.val + " - Options";
    });
}