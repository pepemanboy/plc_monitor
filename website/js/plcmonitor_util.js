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

/*** EVENT FUNCTIONS */

/**
 *	Set active navbar item.
 *
 *	@param {string} item_name name of the item
 */
function activeNavbarItem(item_name) {
	$("#navbar-item-" + item_name).addClass("active").attr("href", "#");
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


/*** POST PARAMETER AND RESPONSE HANDLING UTILITIES */

/**
 * Get php variable.
 *
 * @param {string} response_str Post response message
 * @param {string} variable_str Name of variable to look for
 * @return {mixed} variable value
 */
function getPhpVariable(response_str, variable_str) {
	var varIndex = response_str.indexOf(variable_str); // Index of variable
	var openParIndex = response_str.indexOf("(", varIndex); // Open Parentheses index
	var closedParIndex = response_str.indexOf(")", varIndex); // Open Parentheses index
	var value = response_str.substring(openParIndex + 1, closedParIndex); // Text inside parentheses
	return value;
}

/**
 * Get php array.
 *
 * @param {string} response_str Post response message
 * @param {string} variable_str Name of variable to look for
 * @return {array} array value
 */
function getPhpArray(response_str, variable_str) {
	var str = getPhpVariable(response_str, variable_str);
	var arr = str.split(',');
	return arr;
}

/**
 * Get php array with error and empty flags.
 *
 * @param {string} response_str Post response message
 * @param {string} variable_str Name of variable to look for
 * @return {array} array value with error and empty flags.
 */
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

/**
 * Get php variable with error and empty flags.
 *
 * @param {string} response_str Post response message
 * @param {string} variable_str Name of variable to look for
 * @return {mixed} value with error and empty flags.
 */
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

/**
 * Check error code.
 *
 * @param {string} error_code
 * @return {boolean} true if OK, else false.
 */
function plcOk(error_code) {
	if (!error_code)
		return false;
	var ok_code = "OK";
	return (error_code == ok_code);
}

/*** CSV UTILITIES */

/**
 * Create points array from two arrays.
 *
 * @param {mixed} dates array 1.
 * @param {mixed} values array 2.
 * @return {mixed} points {fecha, valor}[]
 */
function arraysToPoints(dates, values) {
	var points = [];
	for (var i = 0; i < dates.length; i++)
		points.push({
			fecha: dates[i],
			valor: values[i]
		});
	return points;
}

/**
 * Convert array of objects to csv
 */
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

/**
 * Download CSV file
 */
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
 * Set webpage title.
 *
 * @param {string} module_name
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
 *
 * @param {string} text Debug content
 */
function debug(text) {
	$("#debug-row").text(text);
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
 * Report status of module
 * @param {string} status Status of module
 */
function moduleStatus(status) {
	$("#status-indicator").text("Status: " + status);
}