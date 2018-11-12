/** 
 * Javascript for manager.php
 *
 * @author Pepe Melendez
 */

g_plcs = [];

const READY_INPUT_VALUES = 1 << 0;
const READY_OUTPUT_VALUES = 1 << 1;
const READY_NAMES = 1 << 2;
const READY_ALL = READY_INPUT_VALUES | READY_OUTPUT_VALUES | READY_NAMES;

/**
 * On document load. Set active navbar item, update table.
 */
$(document).ready(function() {
	setTitle("Detail");
	activeNavbarItem("detail");
	updatePlcs();
});

/**
 * Update detail table
 */

function updatePlcs() {
	moduleStatus("Querying table");
	$.post("modules/post.php", {
			module: "tabla_plcs",
			operation: "get",
			format: "array",
		},
		function(data, status) {
			var err = getPhpVar(data, "error").val;
			if (!plcOk(err))
				return;

			var ids = getPhpArr(data, "ids");
			if (ids.error)
				return;

			var names = getPhpArr(data, "names");
			if (names.error)
				return;

			for (var i = 0; i < ids.val.length; ++i) {
				g_plcs.push({
					id: ids.val[i],
					name: names.val[i],
					di: [],
					ai: [],
					do: [],
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
			// getOutputs();
		});
}

function getIO() {
	for (var i = 0; i < g_plcs.length; ++i) {
		getInputs(i);
		getOutputs(i);
		getNames(i);
	}
}

function getInputs(n) {
	$.post("modules/post.php", {
			module: "control_inputs",
			plc_number: g_plcs[n].id,
			operation: "get"
		},
		function(data, status) {
			var digital_inputs = getPhpArray(data, "digital_inputs").map(Number);
			var analog_inputs = getPhpArray(data, "analog_inputs").map(Number);
			var err = getPhpVariable(data, "error");
			if (!plcOk(err)) {
				g_plcs[n].err = true;
				return;
			}
			for (i = 0; i < 6; i++) {
				g_plcs[n].di[i].val = digital_inputs[i];
				g_plcs[n].ai[i].val = analog_inputs[i];
			}
			g_plcs[n].ready |= READY_INPUT_VALUES;
			updateTable();
		});
}

function getOutputs(n) {
	$.post("modules/post.php", {
			module: "control_outputs",
			plc_number: g_plcs[n].id,
			operation: "get"
		},
		function(data, status) {
			var digital_outputs = getPhpArray(data, "digital_outputs").map(Number);
			var err = getPhpVariable(data, "error");
			if (!plcOk(err)) {
				g_plcs[n].err = true;
				return;
			}
			for (i = 0; i < 6; i++) {
				g_plcs[n].do[i].val = digital_outputs[i];
			}
			g_plcs[n].ready |= READY_OUTPUT_VALUES;
			updateTable();
		});
}

function getNames(n) {
	$.post("modules/post.php", {
			module: "config_program",
			plc_number: g_plcs[n].id,
			operation: "get"
		},
		function(data, status) {

			var err = getPhpVariable(data, "error");
			if (!plcOk(err)) {
				g_plcs[n].err = true;
				return;
			}

			for (var i = 1; i <= 6; i++) {
				ai_name = getPhpArray(data, "ai" + i)[0];
				di_name = getPhpArray(data, "di" + i)[0];
				do_name = getPhpArray(data, "do" + i)[0];

				g_plcs[n].ai[i - 1].name = ai_name;
				g_plcs[n].di[i - 1].name = di_name;
				g_plcs[n].do[i - 1].name = do_name;
			}
			g_plcs[n].ready |= READY_NAMES;
			updateTable();
		});
}

function updateTable() {
	// Check if plcs finished loading
	for (var i = 0; i < g_plcs.length; ++i) {
		if (g_plcs[i].err) {
			moduleStatus("Error querying table");
			return false;
		}
		if (g_plcs[i].ready != READY_ALL) return false;
	}

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
			do_class = "btn " + (g_plcs[i].do[j].val ? "btn-success" : "btn-secondary");
			do_txt = "<button type = 'button' class = '" + do_class + "'>" + do_val + "</button>";
			$("#" + row_name).append("<td data-toggle='tooltip' data-placement='top' title='" + g_plcs[i].do[j].name + "'>" + do_txt + "</td>");
		}
	}
	$('[data-toggle="tooltip"]').tooltip();
	moduleStatus("Table query OK");


}


/**
 *	Notify user through modal.
 */

function notify(text, title = "Notificación") {
	$("#notif-modal-titulo").text(title);
	$("#notif-modal-body").text(text);
	$("#notif-modal").modal("show");
}

/**
 * Report status of module
 * @param {string} status Status of module
 */
function moduleStatus(status) {
	$("#status-indicator").text("Status: " + status);
}