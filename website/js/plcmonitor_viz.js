/* Constants */
var OUTPUT_COUNT = 6;

// Global variables
var g_plc = 0;
var g_plc_name = "";
var g_signal_number = 0;
var g_signal_type = "";
var g_signal_name = "";

var g_di_names = [];
var g_ai_names = [];
var g_do_names = [];

var g_dates = [];
var g_values = [];

var g_selected_signals = new Array();
var g_graph_signals =[];

// On load
$( document ).ready(function() {
  // Set active menu
  $("#navbar-item-viz").addClass("active");
  $("#navbar-item-viz").attr("href", "#");
  updateChart([],[],"Gráfica");

  // Default dates
  today = new Date();
  today_10 = new Date();
  today_10.setDate(today_10.getDate() - 10);
  $('#datetimepicker2').datetimepicker({ date: today });
  $('#datetimepicker1').datetimepicker({ 
  	date: new Date(today_10)
  });

  // $("#datetimepicker2").datetimepicker("setDate", moment().format('YYYY-MM-DD HH:mm:ss'));

});

// Cuando se pica algun plc en el dropdown, actualizar g_plc
$('.dropdown-plc').click(function(){
	$(".plc-dropdown-menu").text($(this).text());
	$(".plc-dropdown-menu").attr("data-plc-name",$(this).attr("data-plc-name"));
	g_plc_name = $(this).attr("data-plc-name");
	g_plc = Number($(this).attr('data-plc-number'));
	$(".senales-dropdown-menu").text('Selecciona una senal');
	g_signal_number = 0;
	g_signal_type = "";	
	updateSignalDropdown(g_plc);
});

// Cuando se pica alguna senal en el dropdown, actualizar g_signal
$('.dropdown-senales').click(function(){
	if(g_plc < 1)
	{
		vizStatus("Ningun PLC seleccionado");
		return;
	}
	$(".senales-dropdown-menu").text($(this).text());
	g_signal_number = Number($(this).attr('data-signal-number'));
	g_signal_type = $(this).attr('data-signal-type');
	g_signal_name = $(this).attr('data-signal-name');

	var s = {plc_number:g_plc, signal_type: g_signal_type, signal_number: g_signal_number};
	if (!g_selected_signals.some(e => e.signal_number == g_signal_number && e.plc_number == g_plc && e.signal_type == g_signal_type)) {
	  s.signal_name = g_plc_name + " - " + g_signal_name;
	  g_selected_signals.push(s);		
		addSelectedSignal();
	}

	vizStatus("OK");
});

// Update signal dropdown names. n is plc number
function updateSignalDropdown(n)
{
	if(g_plc < 1)
		return false;

	vizStatus("Querying signal names");

	$.post("config_program.php",
	{
		plc_number: n,
		operation: "get"
	},
	function(data,status){

		var err = getPhpVariable(data, "error"); 
		vizStatus(err);
		if(!plcOk(err))
			return;

		for(var i = 1; i <= 6; i++)
		{
			di = getPhpArray(data,"di" + i);	  		
			ai = getPhpArray(data,"ai" + i);
			g_do_names[i-1] = getPhpArray(data,"do" + i)[0];
			g_di_names[i-1] = di[0];
			g_ai_names[i-1] = ai[0];

			$("#viz-signal-dropdown-di" + i).text("DI" + i + ": " + di[0]);
			$("#viz-signal-dropdown-di" + i).attr("data-signal-name", di[0]);
			$("#viz-signal-dropdown-ai" + i).text("AI" + i + ": " + ai[0]);
			$("#viz-signal-dropdown-ai" + i).attr("data-signal-name", ai[0]);
		}

	});  
}

$("#viz-visualizar-fechas-boton").click(function(){
	// Disabled status
	if($(this).hasClass( "disabled" ))
		return false;
	// No dates
	if($("#datetimepicker1").val() == "" || $("#datetimepicker2").val() == "" )
		return false;

	if (g_selected_signals.length < 1)
	{
		vizStatus("Selecciona una senal");
		return false;
	}

	// Cambiar a formato de base de datos
	var fecha1 = moment($("#datetimepicker1").val(), 'MM/DD/YYYY hh:mm A').format('YYYY-MM-DD HH:mm:ss');
	var fecha2 = moment($("#datetimepicker2").val(), 'MM/DD/YYYY hh:mm A').format('YYYY-MM-DD HH:mm:ss');

	getGraphSignals(fecha1, fecha2);
	$("#viz-csv-boton").removeClass("disabled");
});

function getGraphSignals(date1, date2)
{
	g_graph_signals = {signals: [], error: "OK" };
	for(var i = 0; i < g_selected_signals.length; i++)
	{
		var s = g_selected_signals[i];
		$.post("viz_graph.php",
		{
			plc_number: s.plc_number,
			signal_number: s.signal_number,
			signal_type: s.signal_type,
			signal_name: s.signal_name,
			operation: "get",
			date_start: date1,
			date_end: date2
		},
		function(data,status){
			var err = getPhpVariable(data, "error"); 
			var v = getPhpArray(data, "values").map(Number);
			var d = getPhpArray(data, "dates");
			var n = getPhpVariable(data, "name");
			var sig = {values: v, dates: d, name: n};
			if (!plcOk(err))
				g_graph_signals.error = "ERROR";
			g_graph_signals.signals.push(sig);
			graphSignals();
		}); 
	}
}

function graphSignals()
{
	// Not yet completed
	if(g_selected_signals.length > g_graph_signals.signals.length)
		return false;

	if (!plcOk(g_graph_signals.error))
	{
		vizStatus("g_selected_signals.error");
		return false;
	}

	// Compute data
	var dataArray = new Array();
	for(var i = 0; i < g_graph_signals.signals.length; i ++)
	{
		var s = g_graph_signals.signals[i];
		var dataPoints = [];
		for(var j = 0; j < s.values.length; j ++)
		{
			x = new Date(moment(s.dates[j],'YYYY-MM-DD HH:mm:ss').toDate());
			y = s.values[j];
			dataPoints.push({
				x: x,
				y: y
			});
		}
		dataArray.push({dataPoints: dataPoints, name: s.name, showInLegend: true, type: "spline"});
	}

	var chart = new CanvasJS.Chart("chartContainer", {
		animationEnabled: true,
		title:{
			text: "Señales"
		},
		axisX: {
			valueFormatString: "DD MMM,YY"
		},
		axisY: {
			title: "Valor",
			includeZero: false
		},
		legend:{
			cursor: "pointer",
			fontSize: 16,
			itemclick: toggleDataSeries
		},
		toolTip:{
			shared: false
		},
		data: dataArray
	});
	chart.render();

	function toggleDataSeries(e){
		if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
			e.dataSeries.visible = false;
		}
		else{
			e.dataSeries.visible = true;
		}
		chart.render();
	}

}

function getSignal(plc_number, signal_number, signal_type, date1, date2)
{
	// Argument check
	if(plc_number < 1 || signal_number < 1 || signal_type == "")
		return false;

	vizStatus("Querying signal");

	$.post("viz_graph.php",
	{
		plc_number: plc_number,
		signal_number: signal_number,
		signal_type: signal_type,
		operation: "get",
		date_start: date1,
		date_end: date2
	},
	function(data,status){
		var err = getPhpVariable(data, "error"); 
		g_values = getPhpArray(data, "values").map(Number);
		g_dates = getPhpArray(data, "dates");

		updateChart(g_dates, g_values, "PLC " + g_plc + "  " + g_signal_type.toUpperCase() + " " + g_signal_number);

		vizStatus(err);
		if(!plcOk(err))
			return;
	}); 
}

$("#viz-csv-boton").click(function(){
	if($(this).hasClass( "disabled" ))
		return false;	
	downloadCSV({ filename: "plc" + g_plc + "_" + g_signal_type + g_signal_number + ".csv" });
});

$('.datetimepicker-input').on('input',function(e){
    // No dates
    if($("#datetimepicker1").val() == "" || $("#datetimepicker2").val() == "" )
    	return false;
    $("#viz-visualizar-fechas-boton").removeClass("disabled");
});

function updateChart(dates, values, title)
{   
	var data = [];
	var dataSeries = { type: "line" };
	var dataPoints = [];
	for (var i = 0; i < dates.length; i ++) 
	{
		x = new Date(moment(dates[i],'YYYY-MM-DD HH:mm:ss').toDate());
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
    	title:{
    		text: title
    	},
    	toolTip: {
	      	contentFormatter: function (e) {
	          var content = "";
	          for (var i = 0; i < e.entries.length; i++){
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

function debugText(txt)
{
	$("#debug-row").text(txt);
}

// Report input status
function vizStatus(status)
{
	$("#viz-status-indicator").text("Status: " + status);
}

$(document).on("click" , '.viz-selected-signal', function(){
	var s = {
		plc_number: $(this).attr("data-plc-number"), 
		signal_type: $(this).attr("data-signal-type"), 
		number: $(this).attr("data-signal-number")
	};
	
	var index = g_selected_signals.findIndex(e => e.number == s.signal_number && e.plc_number == s.plc_number && e.signal_type == s.signal_type);
	if (index !== -1) g_selected_signals.splice(index, 1);

 	$(this).remove();
});

 function addSelectedSignal()
 {
 	var s = "PLC: " + g_plc_name + " Signal: "  + g_signal_name;
 	$("#viz-selected-signals-group").append("<button type='button' class='btn btn-sm btn-secondary viz-selected-signal' data-plc-number = " + g_plc + " data-signal-number = " + g_signal_number + " data-signal-type = '" + g_signal_type + "'>" + s + " <strong><span>&times;</span></strong></button>").append(" ");
 }