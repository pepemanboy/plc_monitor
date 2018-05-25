/* Constants */
var OUTPUT_COUNT = 6;

// Global variables
var g_plc = 0;
var g_signal_number = 0;
var g_signal_type = "";
var g_action_delete = 0;

var g_di_names = [];
var g_ai_names = [];
var g_do_names = [];

var g_dates = [];
var g_values = [];

var g_actions = [];

// On load
$( document ).ready(function() {
  // Set active menu
  $("#navbar-item-viz").addClass("active");
  $("#navbar-item-viz").attr("href", "#");
  updateChart([],[],"Gráfica");
});

// Cuando se pica algun plc en el dropdown, actualizar g_plc
$('.dropdown-plc').click(function(){
	$(".plc-dropdown-menu").text($(this).text());
	g_plc = Number($(this).attr('data-plc-number'));
	$(".senales-dropdown-menu").text('Selecciona una senal');
	g_signal_number = 0;
	g_signal_type = "";	
	updateSignalDropdown(g_plc);
	// displayActions([],0,0,0);
	$("#viz-agregar-accion-boton").text("Selecciona una señal");
	getActions(g_plc,0,0);
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
	getActions(g_plc, g_signal_number, g_signal_type);
	var sn = g_signal_type.toUpperCase() + g_signal_number;
	$("#viz-agregar-accion-boton").text("Agregar accion a " + sn);
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
function getActions(plc_number, signal_number, signal_type)
{
	vizStatus("Querying actions");
	$.post("viz_action.php",
	{
		plc_number: plc_number,
		operation: "get"
	},
	function(data,status)
	{
		var err = getPhpVariable(data, "error"); 
		vizStatus(err);
		if(!plcOk(err))
			return;

		var err = getPhpArr(data, "ids").error;
		var ids = getPhpArr(data, "ids");
		var inputs = getPhpArray(data, "inputs");
		var thresholds = getPhpArray(data, "thresholds");
		var updowns = getPhpArray(data, "updowns");
		var outputs = getPhpArray(data, "outputs");
		var emails = getPhpArray(data, "emails");
		var notification_intervals_s = getPhpArray(data, "notification_intervals_s");
		var action_types = getPhpArray(data, "action_types");
		var delays_s = getPhpArray(data, "delays_s");

		g_actions = [];
		if(!err)
		{
			for(var i = 0; i < inputs.length; i ++)
			{
				g_actions.push({
					ID: ids[i],
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
		}
		
		displayActions(g_actions, signal_number, signal_type);
	}); 
}

function displayActions(actions, signal_number, signal_type)
{
	// Filter for selected input
	var signal_name = signal_type + signal_number;
	var inputActions = actions.filter(function (el) {
	  return el.input == signal_name;
	});
	inputActions = actions;
	if (inputActions.length < 1)
	{
		$("#viz-actions-row").html("");	
		return;
	}
	$.post("viz_action_box.php",
	{
		number_of_actions: inputActions.length 
	},
	function(data,status)
	{
		var err = getPhpVariable(data, "error"); 
		vizStatus(err);
		if(!plcOk(err))
			return;

		var a = getPhpVariable(data, "table");
		$("#viz-actions-row").html(a);	
		fillActions(inputActions);		
	}); 
}

function fillActions(actions)
{
	for(var i = 1; i <= actions.length; i ++)
	{
		var action = actions[i-1];
		$("#viz-action-id" + i).text("Accion para " + action.input.toUpperCase());
		$("#viz-action-threshold" + i).val(action.threshold).prop('disabled', true);
		$("#viz-action-updown" + i).prop("checked",action.updown > 0 ? true : false).prop('disabled', true);
		var do_txt = "Ninguna salida";
		if(action.output > 0)
			do_txt = "DO" + action.output + " Nombre: " + g_do_names[action.output-1];
		$("#viz-action-output" + i).append($('<option>', {
		    value: 1,
		    text: do_txt
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
		$("#viz-action-borrar-boton" + i).attr("data-action-id", action.ID);
	}
}

$("#viz-agregar-accion-boton").click(function(){
	if($(this).hasClass( "disabled" ))
		return false;
});

// Boton de borrar en una fila. Mostrar modal
$(document).on("click" , '.viz-action-borrar-boton', function(){
  var n = $(this).attr("data-action-id");
  g_action_delete = n;
  $("#viz-borrar-modal-body").text("¿Estás seguro que deseas borrar la accion " + n + "?");
});

// Boton de borrar accion dentro del modal
$('#viz-borrar-modal-boton').click(function(){
  $('#viz-borrar-modal').modal('hide');
  deleteAction(g_plc, g_action_delete);
});

// Borrar accion
function deleteAction(plc_number, action_id)
{
	vizStatus("Deleting action");

	$.post("viz_action.php",
	{
		plc_number: plc_number,
		operation: "delete",
		delete_id: action_id
	},
	function(data,status)
	{
		var err = getPhpVariable(data, "error"); 
		vizStatus(err);
		if(!plcOk(err))
			return;	
		getActions(g_plc, g_signal_number, g_signal_type);
	}); 
}

$("#viz-agregar-accion-boton").click(function(){
  showModalAction();
});

function showModalAction()
{ 
	$.post("viz_action_box.php",
	{
		number_of_actions: 1,
		modal: true
	},
	function(data,status)
	{
		var err = getPhpVariable(data, "error"); 
		vizStatus(err);
		if(!plcOk(err))
			return;

		var a = getPhpVariable(data, "table");
		$("#viz-agregar-modal-body").html(a);	
		formatModalAction();
	}); 
}

function formatModalAction()
{
	$("#viz-action-header0").html("Nueva acción para PLC " + g_plc + " " + g_signal_type.toUpperCase() + g_signal_number);	
	
	// Title

	// Outputs list
	var signal_name = g_signal_type + g_signal_number;
	var inputActions = g_actions.filter(function (el) {
	  return el.output == 0;
	});

	$("#viz-action-output0").append($('<option>', {
		    value: 0,
		    text: "Ninguna salida"
		}));

	for(var i = 1; i <= 6; i ++)
	{
		var txt = "DO" + (i) + " [" + g_do_names[i-1] + "]";
		var a = g_actions.filter(function (el) {
		  return el.output == i;
		});
		if(a.length > 0)
		{
			txt += " usada por " + a[0].input;
		}
		$("#viz-action-output0").append($('<option>', {
		    value: i,
		    text: txt
		}));
	}

	// minutos, horas, etc list
	$("#viz-action-interval-suffix0").append($('<option>', 
		{
		    value: 1,
		    text: "minutos"
		}));
	$("#viz-action-interval-suffix0").append($('<option>', 
		{
		    value: 2,
		    text: "horas"
		}));
	$("#viz-action-interval-suffix0").append($('<option>', 
		{
		    value: 3,
		    text: "dias"
		}));

	//minutos, horas, etc para delay
	$("#viz-action-delay-suffix0").append($('<option>', 
		{
		    value: 1,
		    text: "segundos"
		}));
	$("#viz-action-delay-suffix0").append($('<option>', 
		{
		    value: 2,
		    text: "minutos"
		}));
	$("#viz-action-delay-suffix0").append($('<option>', 
		{
		    value: 3,
		    text: "horas"
		}));

	// Radios
	var $radios = $('input:radio[name=viz-action-radios0]');
    if($radios.is(':checked') === false) {
        $radios.filter('[data-action-type=1]').prop('checked', true);
    }
	
}

$("#viz-agregar-modal-boton").click(function(){
  addAction(g_plc);
});

function addAction(plc_number)
{
	if(!verifyAction())
		return false;

	vizStatus("Adding action");

	// Notification interval time
	var n_t = Number($("#viz-action-interval0").val());
	var n_s = $("#viz-action-interval-suffix0 option:selected").val();
	switch(Number(n_s)){
		case 3: n_t *= 24;
		case 2: n_t *= 60; 
		case 1: n_t *= 60;
		default: break;
	}

	// Email
	var email = "None";
	if(n_t)
	{
		var email = $("#viz-action-email0").val();
	}

	// Delay time
	var d_t = Number($("#viz-action-delay0").val());
	var d_s = $("#viz-action-delay-suffix0 option:selected").val();
	switch(Number(d_s)){		
		case 3: d_t *= 60;
		case 2: d_t *= 60; 
		case 1: d_t *= 1;
		default: break;
	}

	$.post("viz_action.php",
	{
		plc_number: plc_number,
		operation: "add",
		input: ("" + g_signal_type + g_signal_number),
		threshold: $("#viz-action-threshold0").val(),
		updown: $("#viz-action-updown0").prop("checked") ? 1 : 0,
		output: Number($("#viz-action-output0 option:selected").val()),
		email: email,
		notification_interval_s: n_t ,
		action_type: Number($('input[name=viz-action-radios0]:checked').attr("data-action-type")),
		delay_s: d_t
	},
	function(data,status)
	{
		var err = getPhpVariable(data, "error"); 
		vizStatus(err);
		if(!plcOk(err))
			return;	
		getActions(g_plc, g_signal_number, g_signal_type);
	}); 
}

function verifyAction()
{
	var r = true;
	// Threshold
	var th = $("#viz-action-threshold0").val();
	if(!th)
	{
		alert("Sin valor de nivel");
		return false;
	}

	// Notifs
	var notif = $("#viz-action-interval0").val();
	if(notif && notif > 0)
	{
		var email = $("#viz-action-email0").val();
		if (!email)
		{
			alert("Agregar email o quitar intervalo");
			return false;
		}
	}

	// Temporizador
	var radios = $('input[name=viz-action-radios0]:checked').attr("data-action-type");
	if(radios && radios == 2)
	{
		var delay = $("#viz-action-delay0").val();
		if (!delay || delay < 0)
		{
			alert("Sin valor de temporizador");
			return false;
		}
	}
	
	return true;
}

