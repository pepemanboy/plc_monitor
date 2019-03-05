/** 
 * Javascript for detail.php
 *
 * @author Pepe Melendez
 */

/*** CONSTANTS */
const READY_INPUT_VALUES = 1 << 0;
const READY_OUTPUT_VALUES = 1 << 1;
const READY_NAMES = 1 << 2;
const READY_ALL = READY_INPUT_VALUES | READY_OUTPUT_VALUES | READY_NAMES;

/*** GLOBAL VARIABLES */
g_plcs = []; ///< Global PLCs object. {id, name, ai[], di[], do[], err, ready}[]

/*** EVENT FUNCTIONS */

/**
 * Document. On load.
 *
 * Set webpage title, active navbar item, update table.
 */
$(document).ready(function() {
	setTitle("Detail");
	activeNavbarItem("detail");
	updatePlcs();
});

/**
 * Receive table button. On click.
 *
 * Update table.
 */
$('#detail-receive-boton').click(function() {
	updatePlcs();
});


/**
* Output set button. On click.
*/
$(document).on("click", '.do-button', function() {
	var val = $(this).data("do-value");
	val = val ? 0 : 1;
	var plc_index = $(this).data("plc-index");
	var do_index = $(this).data("do-index");
	g_plcs[plc_index].do[do_index].val = val;

	$(this).data("do-value", val);
	if (val)
	{
		$(this).removeClass("btn-secondary");
		$(this).addClass("btn-success");
		$(this).text("ON");
	} else
	{
		$(this).removeClass("btn-success");
		$(this).addClass("btn-secondary");
		$(this).text("OFF");
	}
});

/**
* Send outputs button. On click.
*/
$(document).on("click", '.send-button', function() {
	var plc_index = $(this).data("plc-index");
	sendOutputs(plc_index);
});

/*** CUSTOM FUNCTIONS */

g_data = 0;

/**
 * Update plcs information.
 *
 * Populate g_plcs global object with ids and names. Call getIo.
 */
function updatePlcs() {
	moduleStatus("Querying table");

	$.post("modules/post.php", {
			module: "tabla_plcs",
			operation: "get",
			format: "array",
		},
		function(data, status) {

			var json_data = jQuery.parseJSON(data);
			g_data = json_data;

			var err = json_data.error;

			detailStatus(err);
			if (!plcOk(err))
				return;

			var ids = json_data.ids;
			if (!ids)
				return;

			var names = json_data.names;
			if (!names)
				return;

			g_plcs = Array();

			for (var i = 0; i < ids.length; ++i) {
				g_plcs.push({
					id: ids[i],
					name: names[i],
					di: [],
					ai: [],
					do: [],
					confirmation: 0,
					err: false,
					ready: 0
				});
				for (var j = 0; j < 6; j++) {
					g_plcs[i].ai.push({
						val: 0,
						name: ""
					});
					g_plcs[i].di.push({
						val: 0,
						name: ""
					});
					g_plcs[i].do.push({
						val: 0,
						name: ""
					});
				}
			}

			getIO();
		});
}

/**
 * Populate g_plcs global object with inputs, outputs and names.
 */
function getIO() {
	for (var i = 0; i < g_plcs.length; ++i) {
		getInputs(i);
		getOutputs(i);
		getNames(i);
	}
}

/**
 * Populate g_plcs global object with inputs.
 *
 * @param {integer} n PLC index
 */
function getInputs(n) {
	$.post("modules/post.php", {
			module: "control_inputs",
			plc_number: g_plcs[n].id,
			operation: "get"
		},
		function(data, status) {

			var json_data = jQuery.parseJSON(data);

			var err = json_data.error;
			var digital_inputs = json_data.digital_inputs;
			var analog_inputs = json_data.analog_inputs;

			if (!digital_inputs || !analog_inputs || !plcOk(err))
			{
				g_plcs[n].err = true;
				return;
			}

			digital_inputs = digital_inputs.map(Number);
			analog_inputs = analog_inputs.map(Number);

			for (i = 0; i < 6; i++) {
				g_plcs[n].di[i].val = digital_inputs[i];
				g_plcs[n].ai[i].val = analog_inputs[i];
			}
			g_plcs[n].ready |= READY_INPUT_VALUES;
			updateTable();
		});
}

/**
 * Populate g_plcs global object with outputs.
 *
 * @param {integer} n PLC index
 */
function getOutputs(n) {
	$.post("modules/post.php", {
			module: "control_outputs",
			plc_number: g_plcs[n].id,
			operation: "get"
		},
		function(data, status) {
			var json_data = jQuery.parseJSON(data);

			var err = json_data.error;
			var digital_outputs = json_data.digital_outputs;
			var confirmation = json_data.confirmation;

			if (!digital_outputs || !plcOk(err))
			{
				g_plcs[n].err = true;
				return;
			}

			for (i = 0; i < 6; i++) {
				g_plcs[n].do[i].val = digital_outputs[i];
			}
			g_plcs[n].ready |= READY_OUTPUT_VALUES;
			g_plcs[n].confirmation = confirmation;
			updateTable();
		});
}

g_arr = 0;

/**
* Send outputs to server. Update table if successful.
*
* @param {integer} n PLC index
*/
function sendOutputs(n)
{

	var arr = Array();
	for(var i = 0; i < 6; ++i)
		arr.push(g_plcs[n].do[i].val);

	var id = g_plcs[n].id;

	moduleStatus("Sending PLC " + id + " outputs");

	$.post("modules/post.php", {
			module: "control_outputs",
			plc_number: id,
			outputs: arr,
			operation: "set"
		},
		function(data, status) {
			var json_data = jQuery.parseJSON(data);

			var err = json_data.error;
			moduleStatus("Send PLC " + id + " outputs " + err);
			if (!plcOk(err))
				return;

			updatePlcs();
		});
}


/**
 * Populate g_plcs global object with io names.
 *
 * @param {integer} n PLC id
 */
function getNames(n) {
	$.post("modules/post.php", {
			module: "config_program",
			plc_number: g_plcs[n].id,
			operation: "get"
		},
		function(data, status) {

			var json_data = jQuery.parseJSON(data);

			var err = json_data.error;
			if (!plcOk(err))
			{
				g_plcs[n].err = true;
				return;
			}

			for (var i = 0; i < 6; i++) {
				ai_name = json_data.ai[i].name;
				di_name = json_data.di[i].name;
				do_name = json_data.do[i].name;

				g_plcs[n].ai[i].name = ai_name;
				g_plcs[n].di[i].name = di_name;
				g_plcs[n].do[i].name = do_name;
			}
			g_plcs[n].ready |= READY_NAMES;
			updateTable();
		});
}

/**
 * Update PLC html table and show it.
 */
function updateTable() {
	// Check if plcs finished loading
	for (var i = 0; i < g_plcs.length; ++i) {
		if (g_plcs[i].err) {
			moduleStatus("Error querying table");
			return false;
		}
		if (g_plcs[i].ready != READY_ALL) return false;
	}

	$("#detail-table-body").html("");

	for (var i = 0; i < g_plcs.length; ++i) {
		var row_name = "detail-table-row-" + i;
		$("#detail-table-body").append("<tr id = '" + row_name + "'>");

		$("#" + row_name).append("<td>" + g_plcs[i].name + "</td>");

		for (var j = 0; j < 6; ++j)
			$("#" + row_name).append("<td data-toggle='tooltip' data-placement='top' title='" + g_plcs[i].ai[j].name + "'>" + g_plcs[i].ai[j].val + "</td>");

		for (var j = 0; j < 6; ++j)
			$("#" + row_name).append("<td data-toggle='tooltip' data-placement='top' title='" + g_plcs[i].di[j].name + "'>" + g_plcs[i].di[j].val + "</td>");

		for (var j = 0; j < 6; ++j) {
			do_val = g_plcs[i].do[j].val ? "ON" : "OFF";
			do_class = "btn do-button " + (g_plcs[i].do[j].val ? "btn-success" : "btn-secondary");
			do_txt = "<button data-do-index = " + j + " data-plc-index = " + i + " data-do-value = " + g_plcs[i].do[j].val + " type = 'button' class = '" + do_class + "'>" + do_val + "</button>";
			$("#" + row_name).append("<td data-toggle='tooltip' data-placement='top'  title='" + g_plcs[i].do[j].name + "'>" + do_txt + "</td>");
		}
		conf_val = g_plcs[i].confirmation ? "Pend" : "OK";
		conf_class = "btn " + (g_plcs[i].confirmation ? "btn-warning" : "btn-info")
		$("#" + row_name).append("<td><button type = 'button' class = '" + conf_class + "'>" + conf_val + "</button></td>");

		$("#" + row_name).append("<td><button data-plc-index = " + i + " type = 'button' class = 'send-button btn btn-light'> Enviar </button></td>");
	}
	$('[data-toggle="tooltip"]').tooltip({trigger : 'hover'});
	moduleStatus("Table query OK");
}

/**
 * Notify user through modal.
 *
 * @param {string} text Inner content of modal
 * @param {string} title Title of modal
 */
function notify(text, title = "Notificación") {
	$("#notif-modal-titulo").text(title);
	$("#notif-modal-body").text(text);
	$("#notif-modal").modal("show");
}

/**
 * Report status of module.
 *
 * @param {string} status
 */
function detailStatus(status) {
	$("#detail-status-indicator").text("Status: " + status);
}