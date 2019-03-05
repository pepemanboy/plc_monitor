/** 
 * Javascript for config.php
 *
 * @author Pepe Melendez
 */

/*** CONSTANTS */
var OUTPUT_COUNT = 6;

/*** GLOBAL VARIABLES */
var g_plc = 0; ///< Selected PLC id

/*** EVENT FUNCTIONS */

/**
 * Document. On load.
 *
 * Set webpage title, active navbar item.
 */
$(document).ready(function() {
	setTitle("Config");
	$("#navbar-item-config").addClass("active");
	$("#navbar-item-config").attr("href", "#");
});

/**
 * Dropdown plc. On click.
 *
 * Update text, set g_plc with selected plc's id. Get config.
 */
$('.dropdown-plc').click(function() {
	$(".plc-dropdown-menu").text($(this).text());
	g_plc = Number($(this).attr('data-plc-number'));
	getConfig(g_plc);
});

/** 
 * Set config button. On click.
 *
 * Set config.
 */
$('#config-programar-boton').click(function() {
	setConfig(g_plc);
});

/**
 * Reset button. On click.
 *
 * Send reset values to db table.
 */
$('.config-reset-boton').click(function() {
	var v = Array();
	var b = false;
	for (var i = 0; i < 6; i++) {
		v.push($('#config-reset-input-' + (i + 1)).val());
		if (v[i] == "") {
			v[i] = -1;
		} else {
			b = true;
		}
	}
	if (!b) {
		alert("Escribe algun valor de reset");
		return false;
	}
	configStatus("Sending reset");
	$.post("modules/post.php", {
			module: "reset_counter",
			plc_number: g_plc,
			operation: "set",
			r1: v[0],
			r2: v[1],
			r3: v[2],
			r4: v[3],
			r5: v[4],
			r6: v[5],
		},
		function(data, status) {
			var json_data = jQuery.parseJSON(data);

			var err = json_data.error;
			configStatus(err);
			if (!plcOk(err))
				return;
		});
});

/*** CUSTOM FUNCTIONS */

/**
 * Get configuration from db.
 *
 * @param {integer} id PLC id.
 */
function getConfig(id) {
	if (id < 1)
		return false;

	configStatus("Querying config");

	$.post("modules/post.php", {
			module: "config_program",
			plc_number: id,
			operation: "get"
		},
		function(data, status) {

			var json_data = jQuery.parseJSON(data);

			var err = json_data.error;
			configStatus(err);
			if (!plcOk(err))
				return;

			$(".config-reset-boton").attr("disabled", "disabled");

			for (var i = 1; i <= 6; i++) {
				var di = json_data.di[i-1];
				var ai = json_data.ai[i-1];
				var dout = json_data.do[i-1];

				$("#name-di" + i).val(di.name);
				$("#freq-di" + i).val(di.freq);
				var c = di.count > 0 ? true : false;
				$("#count-di" + i).prop("checked", c);
				$('#config-reset-input-' + i).val("");

				// Enable / disable reset things
				if (!c) {
					$("#config-reset-input-" + i).attr("disabled", "disabled");
				} else {
					$("#config-reset-input-" + i).removeAttr("disabled");
					$(".config-reset-boton").removeAttr("disabled");
				}

				$("#name-ai" + i).val(ai.name);
				$("#freq-ai" + i).val(ai.freq);
				$("#gain-ai" + i).val(ai.gain);
				$("#offs-ai" + i).val(ai.offset);

				$("#name-do" + i).val(dout.name);
			}
		});
}

/**
 * Set configuration in db table.
 *
 * @param {integer} id PLC id.
 */
function setConfig(id) {
	if (id < 1) {
		configStatus("Selecciona un PLC");
		return false;
	}

	configStatus("Setting config");

	var digital_inputs = new Array();
	var analog_inputs = new Array();
	var digital_outputs = new Array();

	for (var i = 1; i <= 6; i++) {
		di = new Array();
		di[0] = $("#name-di" + i).val();
		di[1] = $("#freq-di" + i).val();
		di[2] = $("#count-di" + i).prop("checked");
		digital_inputs.push(di);

		ai = new Array();
		ai[0]= $("#name-ai" + i).val();
		ai[1] = $("#freq-ai" + i).val();
		ai[2] = $("#gain-ai" + i).val();
		ai[3] = $("#offs-ai" + i).val();
		analog_inputs.push(ai);

		dout = new Array();
		dout[0] = $("#name-do" + i).val();
		digital_outputs.push(dout);
	}

	$.post("modules/post.php", {
			module: "config_program",
			plc_number: id,
			operation: "set",
			di: digital_inputs,
			ai: analog_inputs,
			dout: digital_outputs
		},
		function(data, status) {
			var json_data = jQuery.parseJSON(data);

			var err = json_data.error;
			configStatus(err);
			if (!plcOk(err))
				return;
			getConfig(id);
		});
}

/**
 * Report status of module.
 *
 * @param {string} status
 */
function configStatus(status) {
	$("#config-status-indicator").text("Status: " + status);
}