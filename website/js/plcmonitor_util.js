/**
 *	PLC Monitor utilities
 *
 *	@author Pepe Melendez
 */

/* Action types */
const ACTION_NONE = 0;
const ACTION_PERMANENT = 1;
const ACTION_EVENT = 2;
const ACTION_DELAY = 3;

/* Permissions */
const PERMISSIONS_OUTPUTS = 1 << 0;
const PERMISSIONS_ACTIONS = 1 << 1;

/* Accounts */
const ADMIN_USER_ID = 0;

/**
 *	Set active navbar item.
 *	@param {string} item_name name of the item
 */
function activeNavbarItem(item_name) {
	$("#navbar-item-" + item_name).addClass("active").attr("href", "#");
}

function getPhpVariable(response_str, variable_str) {
	var varIndex = response_str.indexOf(variable_str); // Index of variable
	var openParIndex = response_str.indexOf("(", varIndex); // Open Parentheses index
	var closedParIndex = response_str.indexOf(")", varIndex); // Open Parentheses index
	var value = response_str.substring(openParIndex + 1, closedParIndex); // Text inside parentheses
	return value;
}

function getPhpArray(response_str, variable_str) {
	var str = getPhpVariable(response_str, variable_str);
	var arr = str.split(',');
	return arr;
}

function getPhpArr(response_str, variable_str) {
	var ret = {
		val: [],
		error: false,
		empty: false
	};
	var str = getPhpVar(response_str, variable_str);
	if (str.error)
		ret.error = true;
	if (str.empty)
		ret.empty = true;
	if (!str.error && !str.empty)
		ret.val = str.val.split(',');
	return ret;
}

/* Returns true when errors present */
function plcOk(error_code) {
	var ok_code = "OK";
	return (error_code == ok_code);
}

// Get php variable to var. Return false if does not exist
function getPhpVar(response_str, variable_str) {
	var ret = {
		val: 0,
		error: false,
		empty: false
	};
	var varIndex = response_str.indexOf(variable_str + "(");
	if (varIndex < 0) {
		ret.error = true;
	} else {
		var openParIndex = varIndex + variable_str.length;
		var closedParIndex = response_str.indexOf(")", varIndex); // Open Parentheses index
		var value = response_str.substring(openParIndex + 1, closedParIndex); // Text inside parentheses
		if (value == "") ret.empty = true;
		ret.val = value;
	}
	return ret;
}

function arraysToPoints(dates, values) {
	var points = [];
	for (var i = 0; i < dates.length; i++)
		points.push({
			fecha: dates[i],
			valor: values[i]
		});
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
		data: arraysToPoints(args.dates, args.values)
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

/**
 *	Log out
 */
$("#logout-boton").click(function() {
	$.post("modules/post.php", {
			module: "user_control",
			operation: "logout"
		},
		function(data, status) {
			window.location.replace("login.php");
		});
});

/** 
 * Set webpage title.
 */
function setTitle(module_name) {
	$.post("modules/post.php", {
			module: "customize",
			operation: "get_properties"
		},
		function(data, status) {
			var err = getPhpVar(data, "error").val;
			if (!plcOk(err))
				return;
			var title = getPhpVar(data, "title").val;
			document.title = title + " - " + module_name;
		});
}

/**
 * Debug
 */
function debug(text) {
	$("#debug-row").text(text);
}