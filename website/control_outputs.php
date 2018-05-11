<?php 

$str = "";
$arr = $_POST['outputs'];

for ($i = 0; $i < 6; $i++)
{
	if($arr[$i] == "true")
		$str .= ($i+1) . " ";
}

// Connect to database

// Check if table is present

// If not, create table

// Post the outputs to the table

echo $str;
?>