/** 
 * Javascript for control.php
 *
 * @author Pepe Melendez
 */

/*** CONSTANTS */
var OUTPUT_COUNT = 6;

/*** GLOBAL VARIABLES */
var g_plc = 0; ///< Selected PLC id
var g_confirmationPending = moment(); ///< Datetime of confirmation request

/*** EVENT FUNCTIONS */

/**
 * Document. On load.
 *
 * Set webpage title, active navbar item.
 */
$(document).ready(function() {
	setTitle("Control");
	$("#navbar-item-control").addClass("active");
	$("#navbar-item-control").attr("href", "#");
});

/**
 * Digital output button. On click.
 *
 * Update button text on toggle. Update digital output buttons colors.
 */
$('.button-do').click(function() {
	var a = $(this).text();
	if ($(this).text() == "ON")
		$(this).text('OFF');
	else
		$(this).text('ON');
	updateButtonColors();
});

/**
 * Get i/o button. On click.
 *
 * Get i/os.
 */
$("#control-recibir-boton").click(function() {
	getIo(g_plc);
});

/**
 * Set outputs button. On click.
 *
 * Set outputs.
 */
$("#control-enviar-boton").click(function() {
	setOutputs(g_plc);
});

/**
 * Dropdown plc. On click.
 *
 * Update text, set g_plc with selected plc's id. Get i/os.
 */
$('.dropdown-plc').click(function() {
	$(".plc-dropdown-menu").text($(this).text());
	g_plc = Number($(this).attr('data-plc-number'));
	getIo(g_plc);
});

/*** CUSTOM FUNCTIONS */

/**
 * Get the value of a digital outputbutton.
 *
 * @param {integer} n digital output button id [1-6]
 * @return {boolean} true if ON, false otherwise.
 */
function getButtonValue(n) {
	var name = '#do' + n;
	var val = $(name).text().indexOf("ON") >= 0 /* ? 1 : 0 */ ;
	return val;
}

/**
 * Get array of digital output buttons values.
 *
 * @return {array} array.
 */
function getButtonValueArray() {
	var arr = {};
	for (var i = 0; i < OUTPUT_COUNT; ++i) {
		var index = 'do' + i;
		arr[i] = getButtonValue(i + 1);
	}
	return arr;
}

/**
 * Get inputs from db table.
 *
 * Show them on table.
 *
 * @param {integer} id PLC id.
 */
function getInputs(id) {
	if (id < 1) {
		inputsStatus("Ningun PLC seleccionado");
		return;
	}

	inputsStatus("Ejecutando consulta");
	$.post("modules/post.php", {
			module: "control_inputs",
			plc_number: id,
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
}

/**
 * Get outputs from db table.
 *
 * Show them on table. Update digital output buttons colors.
 *
 * @param {integer} id PLC id.
 */
function getOutputs(id) {
	if (id < 1) {
		outputsStatus("Ningun PLC seleccionado");
		return;
	}

	outputsStatus("Ejecutando consulta");
	$.post("modules/post.php", {
			module: "control_outputs",
			plc_number: id,
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
}

/**
 * Get both inputs and outputs
 *
 * @param {integer} id PLC id
 */
function getIo(id) {
	getOutputs(id);
	getInputs(id);
}

/**
 * Set outputs in db table.
 *
 * @param {integer} id PLC id
 */
function setOutputs(id) {
	if (id < 1) {
		outputsStatus("Ningun PLC seleccionado");
		return;
	}

	var arr = getButtonValueArray();
	outputsStatus("Enviando datos");
	$.post("modules/post.php", {
			module: "control_outputs",
			plc_number: id,
			outputs: arr,
			operation: "set"
		},
		function(data, status) {
			var err = getPhpVariable(data, "error");
			outputsStatus(err);
			if (!plcOk(err))
				return;
			moduleStatus("Pending PLC " + id);
			g_confirmationPending = moment();
			setTimeout(function() {
				confirmationWait(n);
			}, 5000);
		});
}

/**
 * Wait for confirmation from PLC.
 *
 * Confirmation is defined as the PLC reporting its status in a date posterior from the request sent. Will check every 5 seconds.
 *
 * @param {integer} id PLC id
 */
function confirmationWait(id) {
	$.post("modules/post.php", {
			module: "tabla_plcs",
			plc_number: id,
			operation: "date"
		},
		function(data, status) {
			var err = getPhpVariable(data, "error");
			outputsStatus(err);
			if (!plcOk(err)) {
				moduleStatus("Confirmation Error PLC " + id);
				return;
			}
			var d = moment(getPhpVariable(data, "date"), 'YYYY-MM-DD HH:mm:ss');
			var diff = d.diff(g_confirmationPending);
			if (diff > 0) {
				moduleStatus("Confirmed PLC " + n);
				return;
			}
			setTimeout(function() {
				confirmationWait(id);
			}, 5000);
		});
}


/**
 * Update colors of digital outputs buttons. 
 *
 * Green for ON, gray for OFF.
 */
function updateButtonColors() {
	for (var i = 1; i <= 6; i++) {
		if ($("#do" + i).text() == "OFF") {
			$("#do" + i).removeClass("btn-success");
			$("#do" + i).addClass("btn-secondary");
		} else {
			$("#do" + i).removeClass("btn-secondary");
			$("#do" + i).addClass("btn-success");
		}
	}
}

/**
 * Report status of inputs.
 * @param {string} status Status of inputs.
 */
func

function inputsStatus(status) {
	$("#control-inputs-indicator").text("Inputs status: " + status);
}

/**
 * Report status of outputs.
 * @param {string} status Status of outputs.
 */
function outputsStatus(status) {
	$("#control-outputs-indicator").text("Outputs status: " + status);
}


/**
 * Report status of module.
 * @param {string} status Status of module.
 */
function moduleStatus(status) {
	$("#status-indicator").text("Status: " + status);
}