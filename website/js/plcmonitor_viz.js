/* Constants */
var OUTPUT_COUNT = 6;

// Global variables
var g_plc = 0;
var g_signal_number = 0;
var g_signal_type = "";

var g_di_names = [];
var g_ai_names = [];
var g_do_names = [];

var g_dates = [];
var g_values = [];

// On load
$( document ).ready(function() {
  // Set active menu
  $("#navbar-item-viz").addClass("active");
  $("#navbar-item-viz").attr("href", "#");
  updateChart([],[],"Nada para mostrar");
});

// Cuando se pica algun plc en el dropdown, actualizar g_plc
$('.dropdown-plc').click(function(){
	$(".plc-dropdown-menu").text($(this).text());
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
	$("#viz-agregar-accion-boton").removeClass("disabled");
	vizStatus("OK");
	getActions(g_plc);
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

			$("#viz-signal-dropdown-di" + i).text("DI" + i + ": " + di[0]);
			$("#viz-signal-dropdown-ai" + i).text("AI" + i + ": " + ai[0]);
		}

	});  
}

// Report input status
function vizStatus(status)
{
	$("#viz-status-indicator").text("Status: " + status);
}

$("#viz-visualizar-fechas-boton").click(function(){
	// Disabled status
	if($(this).hasClass( "disabled" ))
		return false;
	// No dates
	if($("#datetimepicker1").val() == "" || $("#datetimepicker2").val() == "" )
		return false;



	// Cambiar a formato de base de datos
	var fecha1 = moment($("#datetimepicker1").val(), 'MM/DD/YYYY hh:mm A').format('YYYY-MM-DD HH:mm:ss');
	var fecha2 = moment($("#datetimepicker2").val(), 'MM/DD/YYYY hh:mm A').format('YYYY-MM-DD HH:mm:ss');

	getSignal(g_plc, g_signal_number, g_signal_type, fecha1, fecha2);	

	$("#viz-csv-boton").removeClass("disabled");
});

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

function debugText(txt)
{
	$("#debug-row").text(txt);
}

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
    	zoomEnabled: true,
    	animationEnabled: true,
    	axisY: {
    		includeZero: false
    	},
    	data: data 
    };

    $("#chartContainer").CanvasJSChart(options);
}

function arraysToPoints(dates, values)
{
	var points = [];
	for(var i = 0; i < dates.length; i ++)
		points.push({fecha: dates[i], valor: values[i]});
	return points;	
}

function convertArrayOfObjectsToCSV(args) {
	var result, ctr, keys, columnDelimiter, lineDelimiter, data;

	data = args.data || null;
	if (data == null || !data.length) {
		return null;
	}

	columnDelimiter = args.columnDelimiter || ',';
	lineDelimiter = args.lineDelimiter || '\n';

	keys = Object.keys(data[0]);

	result = '';
	result += keys.join(columnDelimiter);
	result += lineDelimiter;

	data.forEach(function(item) {
		ctr = 0;
		keys.forEach(function(key) {
			if (ctr > 0) result += columnDelimiter;

			result += item[key];
			ctr++;
		});
		result += lineDelimiter;
	});

	return result;
}

function downloadCSV(args) {
	var data, filename, link;

	var csv = convertArrayOfObjectsToCSV({
		data: arraysToPoints(g_dates,g_values)
	});
	if (csv == null) return;

	filename = args.filename || 'export.csv';

	if (!csv.match(/^data:text\/csv/i)) {
		csv = 'data:text/csv;charset=utf-8,' + csv;
	}
	data = encodeURI(csv);

	link = document.createElement('a');
	link.setAttribute('href', data);
	link.setAttribute('download', filename);
	link.click();
}

// Obtener acciones. N es el numero de plc
function getActions(n)
{
	vizStatus("Querying actions");
	$.post("viz_action.php",
	{
		plc_number: n,
		operation: "get"
	},
	function(data,status)
	{
		var err = getPhpVariable(data, "error"); 
		vizStatus(err);
		if(!plcOk(err))
			return;

		var inputs = getPhpArray(data, "inputs");
		var thresholds = getPhpArray(data, "thresholds");
		var updowns = getPhpArray(data, "updowns");
		var outputs = getPhpArray(data, "outputs");
		var emails = getPhpArray(data, "emails");
		var notification_intervals_s = getPhpArray(data, "notification_intervals_s");
		var action_types = getPhpArray(data, "action_types");
		var delays_s = getPhpArray(data, "delays_s");

		var actions = [];
		for(var i = 0; i < inputs.length; i ++)
		{
			actions.push({
				input: inputs[i], 
				threshold: thresholds[i], 
				updown: updowns[i], 
				output: outputs[i],
				email: emails[i],
				notification_interval_s: notification_intervals_s[i],
				action_type: action_types[i],
				delay_s: delays_s[i]
			});
		}
		displayActions(actions);
	}); 
}

function displayActions(actions)
{
	$.post("viz_action_box.php",
	{
		actions: JSON.stringify(actions)
	},
	function(data,status)
	{
		var err = getPhpVariable(data, "error"); 
		vizStatus(err);
		if(!plcOk(err))
			return;

		var a = getPhpVariable(data, "table");
		$("#debug-row").html(a);	
		fillActions(actions);		
	}); 
}

function fillActions(actions)
{
	for(var i = 1; i <= actions.length; i ++)
	{
		var action = actions[i-1];
		$("#viz-action-threshold" + i).val(action.threshold).prop('disabled', true);
		$("#viz-action-updown" + i).prop("checked",action.updown > 0 ? true : false).prop('disabled', true);
		$("#viz-action-output" + i).append($('<option>', {
		    value: 1,
		    text: "DO" + action.output + " Nombre: " + g_do_names[action.output-1]
		})).prop('disabled', true);
		$("#viz-action-email" + i).val(action.email).prop('disabled', true);
		// Calculate time
		var t = action.notification_interval_s;
		var s = "sec";
		if (t > 60)
		{
			s = "min", t /= 60;
			if (t > 60)
			{
				s = "horas", t /= 60;
				if (t > 24)
					s = "dias", t /= 24;
			}
		}				
		$("#viz-action-interval" + i).val(Math.floor(t)).prop('disabled', true);
		$("#viz-action-interval-suffix" + i).append($('<option>', {
		    value: 1,
		    text: s
		})).prop('disabled', true);
		// Radios
		var selected = action.action_type;
		var $radios = $('input:radio[name=viz-action-radios' + i + ']').prop('disabled', true);
	    if($radios.is(':checked') === false) {
	        $radios.filter('[data-action-type=' + selected + ']').prop('checked', true);
	    }
		$("input[name=viz-action-radios" + i + "]:checked").val();
		// Calculate delay time
		var t = action.delay_s;
		var s = "sec";
		if (t > 60)
		{
			s = "min", t /= 60;
			if (t > 60)
			{
				s = "horas", t /= 60;
				if (t > 24)
					s = "dias", t /= 24;
			}
		}	
		$("#viz-action-delay" + i).val(Math.floor(t)).prop('disabled', true);
		$("#viz-action-delay-suffix" + i).append($('<option>', {
		    value: 1,
		    text: s
		})).prop('disabled', true);
	}
}

$("#viz-agregar-accion-boton").click(function(){
	if($(this).hasClass( "disabled" ))
		return false;
});