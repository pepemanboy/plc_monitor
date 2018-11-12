/* Constants */
var OUTPUT_COUNT = 6;

// Global variables
var g_plc = 0;
var g_confirmationPending = moment();

// On load
$(document).ready(function() {
  setTitle("Control");
  $("#navbar-item-control").addClass("active");
  $("#navbar-item-control").attr("href", "#");
});


// Get the value of a button, boolean
function getButtonValue(n) {
  var name = '#do' + n;
  var val = $(name).text().indexOf("ON") >= 0 /* ? 1 : 0 */ ;
  return val;
}

// Get button values array
function getButtonValueArray() {
  var arr = {};
  for (var i = 0; i < OUTPUT_COUNT; ++i) {
    var index = 'do' + i;
    arr[i] = getButtonValue(i + 1);
  }
  return arr;
}

// Update button text on toggle
$('.button-do').click(function() {
  var a = $(this).text();
  if ($(this).text() == "ON")
    $(this).text('OFF');
  else
    $(this).text('ON');
  updateButtonColors();
});

// Recibir
$("#control-recibir-boton").click(function() {
  getIo(g_plc);
});

// Enviar
$("#control-enviar-boton").click(function() {
  setOutputs(g_plc);
});

// Cuando se pica algun plc en el dropdown, actualizar g_plc
$('.dropdown-plc').click(function() {
  $(".plc-dropdown-menu").text($(this).text());
  g_plc = Number($(this).attr('data-plc-number'));
  getIo(g_plc);
});

// Recibir inputs. n es el numero de plc
function getInputs(n) {
  if (n < 1) {
    inputsStatus("Ningun PLC seleccionado");
    return false;
  }

  inputsStatus("Ejecutando consulta");
  $.post("modules/post.php", {
      module: "control_inputs",
      plc_number: n,
      operation: "get"
    },
    function(data, status) {
      var digital_inputs = getPhpArray(data, "digital_inputs").map(Number);
      var analog_inputs = getPhpArray(data, "analog_inputs").map(Number);
      var err = getPhpVariable(data, "error");
      inputsStatus(err);
      if (!plcOk(err))
        return;
      for (i = 0; i < 6; i++) {
        $("#di" + (i + 1)).text(digital_inputs[i]);
        $("#ai" + (i + 1)).text(analog_inputs[i]);
      }
    });
  return true;
}

// Recibir outputs. n es el numero de plc
function getOutputs(n) {
  if (n < 1) {
    outputsStatus("Ningun PLC seleccionado");
    return false;
  }

  outputsStatus("Ejecutando consulta");
  $.post("modules/post.php", {
      module: "control_outputs",
      plc_number: n,
      operation: "get"
    },
    function(data, status) {
      var digital_outputs = getPhpArray(data, "digital_outputs").map(Number);
      var err = getPhpVariable(data, "error");
      outputsStatus(err);
      if (!plcOk(err))
        return;
      for (i = 0; i < 6; i++) {
        $("#do" + (i + 1)).text(digital_outputs[i] ? "ON" : "OFF");
      }
      updateButtonColors();
    });
  return true;
}

// Recibir inputs y outputs. n es el numero de plc.
function getIo(n) {
  getOutputs(n);
  getInputs(n);
}

// Enviar datos. n es el numero de plc
function setOutputs(n) {
  if (n < 1) {
    outputsStatus("Ningun PLC seleccionado");
    return false;
  }

  var arr = getButtonValueArray();
  outputsStatus("Enviando datos");
  $.post("modules/post.php", {
      module: "control_outputs",
      plc_number: n,
      outputs: arr,
      operation: "set"
    },
    function(data, status) {
      var err = getPhpVariable(data, "error");
      outputsStatus(err);
      if (!plcOk(err))
        return;
      moduleStatus("Pending PLC " + n);
      g_confirmationPending = moment();
      setTimeout(function() {
        confirmationWait(n);
      }, 5000);
    });
}

function confirmationWait(n) {
  $.post("modules/post.php", {
      module: "tabla_plcs",
      plc_number: n,
      operation: "date"
    },
    function(data, status) {
      var err = getPhpVariable(data, "error");
      outputsStatus(err);
      if (!plcOk(err)) {
        moduleStatus("Confirmation Error PLC " + n);
        return;
      }
      var d = moment(getPhpVariable(data, "date"), 'YYYY-MM-DD HH:mm:ss');
      var diff = d.diff(g_confirmationPending);
      if (diff > 0) {
        moduleStatus("Confirmed PLC " + n);
        return;
      }
      setTimeout(function() {
        confirmationWait(n);
      }, 5000);
    });
}

// Report input status
function inputsStatus(status) {
  $("#control-inputs-indicator").text("Inputs status: " + status);
}

// Report outputs status
function outputsStatus(status) {
  $("#control-outputs-indicator").text("Outputs status: " + status);
}

// Update button colors
function updateButtonColors() {
  for (var i = 1; i <= 6; i++) {
    // OFF
    if ($("#do" + i).text() == "OFF") {
      $("#do" + i).removeClass("btn-success");
      $("#do" + i).addClass("btn-secondary");
    }
    // ON
    else {
      $("#do" + i).removeClass("btn-secondary");
      $("#do" + i).addClass("btn-success");
    }
  }
}

/**
 * Report status of module
 * @param {string} status Status of module
 */
function moduleStatus(status) {
  $("#status-indicator").text("Status: " + status);
}