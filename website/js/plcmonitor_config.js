/* Constants */
var OUTPUT_COUNT = 6;

// Global variables
var g_plc = 0;

// On load
$(document).ready(function() {
	setTitle("Config");
	$("#navbar-item-config").addClass("active");
	$("#navbar-item-config").attr("href", "#");
});

// Cuando se pica algun plc en el dropdown, actualizar g_plc
$('.dropdown-plc').click(function() {
	$(".plc-dropdown-menu").text($(this).text());
	g_plc = Number($(this).attr('data-plc-number'));
	getConfig(g_plc);
});

// Cuando se pica algun plc en el dropdown, actualizar g_plc
$('#config-programar-boton').click(function() {
	setConfig(g_plc);
});

// Get configuration from database, n is plc number
function getConfig(n) {
	if (n < 1)
		return false;

	configStatus("Querying config");

	$.post("modules/post.php", {
			module: "config_program",
			plc_number: n,
			operation: "get"
		},
		function(data, status) {
			var err = getPhpVariable(data, "error");
			configStatus(err);
			if (!plcOk(err))
				return;

			$(".config-reset-boton").attr("disabled", "disabled");

			for (var i = 1; i <= 6; i++) {
				var di = getPhpArray(data, "di" + i);
				var ai = getPhpArray(data, "ai" + i);
				var dout = getPhpArray(data, "do" + i);

				$("#name-di" + i).val(di[0]);
				$("#freq-di" + i).val(di[1]);
				var c = di[2] > 0 ? true : false;
				$("#count-di" + i).prop("checked", c);
				$('#config-reset-input-' + i).val("");

				// Enable / disable reset things
				if (!c) {
					$("#config-reset-input-" + i).attr("disabled", "disabled");
				} else {
					$("#config-reset-input-" + i).removeAttr("disabled");
					$(".config-reset-boton").removeAttr("disabled");
				}

				$("#name-ai" + i).val(ai[0]);
				$("#freq-ai" + i).val(ai[1]);
				$("#gain-ai" + i).val(ai[2]);
				$("#offs-ai" + i).val(ai[3]);

				$("#name-do" + i).val(dout[0]);
			}

		});
}

// Set configuration to database, n is plc number
function setConfig(n) {
	if (n < 1) {
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
		digital_inputs[i - 1] = di;

		ai = new Array();
		ai[0] = $("#name-ai" + i).val();
		ai[1] = $("#freq-ai" + i).val();
		ai[2] = $("#gain-ai" + i).val();
		ai[3] = $("#offs-ai" + i).val();
		analog_inputs[i - 1] = ai;

		dout = new Array();
		dout[0] = $("#name-do" + i).val();
		digital_outputs[i - 1] = dout;
	}

	$.post("modules/post.php", {
			module: "config_program",
			plc_number: n,
			operation: "set",
			di: digital_inputs,
			ai: analog_inputs,
			dout: digital_outputs
		},
		function(data, status) {
			// alert(data);
			var err = getPhpVariable(data, "error");
			configStatus(err);
			if (!plcOk(err))
				return;
			getConfig(n);
		});
}

// Reset counter
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
			var err = getPhpVariable(data, "error");
			configStatus(err);
			if (!plcOk(err))
				return;
		});
});

// Report input status
function configStatus(status) {
	$("#config-status-indicator").text("Status: " + status);
}