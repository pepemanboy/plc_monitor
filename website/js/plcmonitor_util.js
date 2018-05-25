function getPhpVariable(response_str, variable_str)
{
  var varIndex = response_str.indexOf(variable_str); // Index of variable
  var openParIndex = response_str.indexOf("(",varIndex); // Open Parentheses index
  var closedParIndex = response_str.indexOf(")",varIndex); // Open Parentheses index
  var value = response_str.substring(openParIndex+1,closedParIndex); // Text inside parentheses
  return value;
}

function getPhpArray(response_str, variable_str)
{
	var str = getPhpVariable(response_str, variable_str);
	var arr = str.split(',');
	return arr;
}

/* Returns true when errors present */
function plcOk(error_code)
{
	var ok_code = "OK";
	return(error_code == ok_code);
}

// Get php variable to var. Return false if does not exist
function getPhpVar(response_str, variable_str)
{
	var ret = {val: 0, error: false};
	var varIndex = response_str.indexOf(variable_str + "(");
	if (varIndex < 0)
	{
		ret.error = true;
	}else
	{
		var openParIndex = varIndex + variable_str.length;
		var closedParIndex = response_str.indexOf(")",varIndex); // Open Parentheses index
		var value = response_str.substring(openParIndex+1,closedParIndex); // Text inside parentheses
		ret.val = value;
	}	
	return ret;
}