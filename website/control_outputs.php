<?php 
echo ("hola");

$str = "";
$arr = $_POST['outputs'];


for ($i = 0; $i < 6; $i++)
{
	if($arr[$i] == "true")
		$str .= ($i+1) . " ";
}



// Connect to database
	include("connect.php");
   	
   	$link=Connection();


// Check if table is present
// Select 1 from table_name will return false if the table does not exist.
$val = mysql_query('Select 1 from `Output_table` LIMIT 1');

if($val !== FALSE)
{

   //POST control outputs
	$query = "INSERT INTO `Output_table` ( `control_outputs`) 
		VALUES ('".$arr."')"; 
   	
   	

}else{
    // If not, create table of 0
     "CREATE TABLE Output_table (
		timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
		control_outputs int(11) NOT NULL,
	)";


    $query = "INSERT INTO `Output_table` ( `control_outputs`) 
		VALUES (0,0,0,0,0,0,)"; 
}





// Post the outputs to the table
    mysql_query($query,$link);
	mysql_close($link);

   	//header("Location: index.php");
	
echo $str;
?>

