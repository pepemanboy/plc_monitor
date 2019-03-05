/** 
 * Javascript for viz.php
 * @author Pepe Melendez
 */

/*** CONSTANTS */
var OUTPUT_COUNT = 6;

/*** GLOBAL VARIABLES */
var g_plc = 0; ///<
var g_plc_name = ""; ///<
var g_signal_number = 0; ///<
var g_signal_type = ""; ///<
var g_signal_name = ""; ///<

var g_di_names = []; ///<
var g_ai_names = []; ///<
var g_do_names = []; ///<

var g_dates = []; ///<
var g_values = []; ///<

var g_selected_signals = new Array(); ///<
var g_graph_signals = []; ///<

/*** EVENT FUNCTIONS */

// On load
/**
*
*/
$(document).ready(function() {
	setTitle("Viz");
	// Set active menu
	$("#navbar-item-viz").addClass("active");
	$("#navbar-item-viz").attr("href", "#");
	updateChart([], [], "Gráfica");

	// Default dates
	today = new Date();
	today_10 = new Date();
	today_10.setDate(today_10.getDate() - 10);
	$('#datetimepicker2').datetimepicker({
		date: today
	});
	$('#datetimepicker1').datetimepicker({
		date: new Date(today_10)
	});

});

// Cuando se pica algun plc en el dropdown, actualizar g_plc
/**
*
*/
$('.dropdown-plc').click(function() {
	$(".plc-dropdown-menu").text($(this).text());
	$(".plc-dropdown-menu").attr("data-plc-name", $(this).attr("data-plc-name"));
	g_plc_name = $(this).attr("data-plc-name");
	g_plc = Number($(this).attr('data-plc-number'));
	$(".senales-dropdown-menu").text('Selecciona una senal');
	g_signal_number = 0;
	g_signal_type = "";
	updateSignalDropdown(g_plc);
});

// Cuando se pica alguna senal en el dropdown, actualizar g_signal
/**
*
*/
$('.dropdown-senales').click(function() {
	if (g_plc < 1) {
		vizStatus("Ningun PLC seleccionado");
		return;
	}
	$(".senales-dropdown-menu").text($(this).text());
	g_signal_number = Number($(this).attr('data-signal-number'));
	g_signal_type = $(this).attr('data-signal-type');
	g_signal_name = $(this).attr('data-signal-name');

	var s = {
		plc_number: g_plc,
		signal_type: g_signal_type,
		signal_number: g_signal_number
	};
	if (!g_selected_signals.some(e => e.signal_number == g_signal_number && e.plc_number == g_plc && e.signal_type == g_signal_type)) {
		s.signal_name = g_plc_name + " - " + g_signal_name;
		g_selected_signals.push(s);
		addSelectedSignal();
	}

	vizStatus("OK");
});

/**
*
*/
$("#viz-visualizar-fechas-boton").click(function() {
	// Disabled status
	if ($(this).hasClass("disabled"))
		return false;
	// No dates
	if ($("#datetimepicker1").val() == "" || $("#datetimepicker2").val() == "")
		return false;

	if (g_selected_signals.length < 1) {
		vizStatus("Selecciona una senal");
		return false;
	}

	// Cambiar a formato de base de datos
	var fecha1 = moment($("#datetimepicker1").val(), 'MM/DD/YYYY hh:mm A').format('YYYY-MM-DD HH:mm:ss');
	var fecha2 = moment($("#datetimepicker2").val(), 'MM/DD/YYYY hh:mm A').format('YYYY-MM-DD HH:mm:ss');

	getGraphSignals(fecha1, fecha2);
	$("#viz-csv-boton").removeClass("disabled");
});

/**
*
*/
$("#viz-csv-boton").click(function() {
	if ($(this).hasClass("disabled"))
		return false;
	for (var i = 0; i < g_graph_signals.signals.length; i++) {
		downloadCSV({
			values: g_graph_signals.signals[i].values,
			dates: g_graph_signals.signals[i].dates,
			filename: g_graph_signals.signals[i].name + ".csv"
		});
	}

});

/**
*
*/
$('.datetimepicker-input').on('input', function(e) {
	// No dates
	if ($("#datetimepicker1").val() == "" || $("#datetimepicker2").val() == "")
		return false;
	$("#viz-visualizar-fechas-boton").removeClass("disabled");
});

/**
*
*/
$(document).on("click", '.viz-selected-signal', function() {
	var s = {
		plc_number: $(this).attr("data-plc-number"),
		signal_type: $(this).attr("data-signal-type"),
		number: $(this).attr("data-signal-number")
	};

	var index = g_selected_signals.findIndex(e => e.number == s.signal_number && e.plc_number == s.plc_number && e.signal_type == s.signal_type);
	if (index !== -1) g_selected_signals.splice(index, 1);

	$(this).remove();
});

/*** CUSTOM FUNCTIONS */


// Update signal dropdown names. n is plc number
/**
*
*/
function updateSignalDropdown(n) {
	if (g_plc < 1)
		return false;

	vizStatus("Querying signal names");
	$.post("modules/post.php", {
			module: "config_program",
			plc_number: n,
			operation: "get"
		},
		function(data, status) {
			var json_data = jQuery.parseJSON(data);

			var err = json_data.error;
			vizStatus(err);
			if (!plcOk(err))
				return;

			for (var i = 1; i <= 6; i++) {
				var di = json_data.di[i-1];
				var ai = json_data.ai[i-1];
				var dout = json_data.do[i-1];


				g_do_names[i-1] = dout.name;
				g_di_names[i - 1] = di.name;
				g_ai_names[i - 1] = ai.name;

				$("#viz-signal-dropdown-di" + i).text("DI" + i + ": " + di.name);
				$("#viz-signal-dropdown-di" + i).attr("data-signal-name", di.name);
				$("#viz-signal-dropdown-ai" + i).text("AI" + i + ": " + ai.name);
				$("#viz-signal-dropdown-ai" + i).attr("data-signal-name", ai.name);
			}

		});
}

/**
*
*/
function getGraphSignals(date1, date2) {
	vizStatus("Querying signals");
	g_graph_signals = {
		signals: [],
		error: "OK"
	};
	for (var i = 0; i < g_selected_signals.length; i++) {
		var s = g_selected_signals[i];
		$.post("modules/post.php", {
				module: "viz_graph",
				plc_number: s.plc_number,
				signal_number: s.signal_number,
				signal_type: s.signal_type,
				signal_name: s.signal_name,
				operation: "get",
				date_start: date1,
				date_end: date2
			},
			function(data, status) {
				var json_data = jQuery.parseJSON(data);

				var err = json_data.error;
				var v = json_data.signal.values.map(Number);
				var d = json_data.signal.dates;
				var n = json_data.name;
				var sig = {
					values: v,
					dates: d,
					name: n
				};
				if (!plcOk(err))
					g_graph_signals.error = "ERROR";
				g_graph_signals.signals.push(sig);
				graphSignals();
			});
	}
}

/**
*
*/
function graphSignals() {
	// Not yet completed
	if (g_selected_signals.length > g_graph_signals.signals.length)
		return false;

	if (!plcOk(g_graph_signals.error)) {
		vizStatus(g_selected_signals.error);
		return false;
	}

	vizStatus("Querying signals OK");

	// Compute data
	var dataArray = new Array();
	for (var i = 0; i < g_graph_signals.signals.length; i++) {
		var s = g_graph_signals.signals[i];
		var dataPoints = [];
		for (var j = 0; j < s.values.length; j++) {
			x = new Date(moment(s.dates[j], 'YYYY-MM-DD HH:mm:ss').toDate());
			y = s.values[j];
			dataPoints.push({
				x: x,
				y: y
			});
		}
		dataArray.push({
			dataPoints: dataPoints,
			name: s.name,
			showInLegend: true,
			type: "spline"
		});
	}

	var chart = new CanvasJS.Chart("chartContainer", {
		animationEnabled: true,
		title: {
			text: "Señales"
		},
		axisX: {
			valueFormatString: "DD MMM,YY"
		},
		axisY: {
			title: "Valor",
			includeZero: false
		},
		legend: {
			cursor: "pointer",
			fontSize: 16,
			itemclick: toggleDataSeries
		},
		toolTip: {
			shared: false,
			contentFormatter: function(e) {
				var content = "";
				for (var i = 0; i < e.entries.length; i++) {
					content = CanvasJS.formatDate(e.entries[i].dataPoint.x, "D/MMM/YYYY HH:mm:ss");
				}
				return content;
			}
		},
		data: dataArray
	});
	chart.render();

	function toggleDataSeries(e) {
		if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
			e.dataSeries.visible = false;
		} else {
			e.dataSeries.visible = true;
		}
		chart.render();
	}

}

/**
*
*/
function updateChart(dates, values, title) {
	var data = [];
	var dataSeries = {
		type: "line"
	};
	var dataPoints = [];
	for (var i = 0; i < dates.length; i++) {
		x = new Date(moment(dates[i], 'YYYY-MM-DD HH:mm:ss').toDate());
		y = values[i];
		dataPoints.push({
			x: x,
			y: y
		});
	}
	dataSeries.dataPoints = dataPoints;
	data.push(dataSeries);

	//Better to construct options first and then pass it as a parameter
	var options = {
		title: {
			text: title
		},
		toolTip: {
			contentFormatter: function(e) {
				var content = "";
				for (var i = 0; i < e.entries.length; i++) {
					content = CanvasJS.formatDate(e.entries[i].dataPoint.x, "D/MMM/YYYY HH:mm:ss");
				}
				return content;
			}
		},
		zoomEnabled: true,
		animationEnabled: true,
		axisY: {
			includeZero: false
		},
		data: data
	};

	$("#chartContainer").CanvasJSChart(options);
}

/**
*
*/
function addSelectedSignal() {
	var s = "PLC: " + g_plc_name + " Signal: " + g_signal_name;
	$("#viz-selected-signals-group").append("<button type='button' class='btn btn-sm btn-secondary viz-selected-signal' data-plc-number = " + g_plc + " data-signal-number = " + g_signal_number + " data-signal-type = '" + g_signal_type + "'>" + s + " <strong><span>&times;</span></strong></button>").append(" ");
}

/**
 * Report status of module.
 *
 * @param {string} status
 */
function vizStatus(status) {
	$("#viz-status-indicator").text("Status: " + status);
}

