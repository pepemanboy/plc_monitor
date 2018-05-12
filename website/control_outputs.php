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

echo("antes");
// Check if table is present
// Select 1 from table_name will return false if the table does not exist
$val= mysqli_query($link, 'Select 1 from `Output_table` LIMIT 1');
echo("despues");

if($val != FALSE)
{
echo("false");
   //POST control outputs
	$query = "INSERT INTO Output_table(control_output_1,control_output_2,control_output_3,control_output_4,control_output_5,control_output_6) 
		VALUES (".$arr[0].",".$arr[1].",".$arr[2].",".$arr[3].",".$arr[4].",".$arr[5].")"; 
   	
   	

}else{
	echo("true");
    // If not, create table of 0
     $query = "CREATE TABLE Output_table (
     timeStamp TIMESTAMP NOT NULL PRIMARY KEY,
     	control_output_1 int(11) NOT NULL,
		control_output_2 int(11) NOT NULL,
		control_output_3 int(11) NOT NULL,
		control_output_4 int(11) NOT NULL,
		control_output_5 int(11) NOT NULL,
		control_output_6 int(11) NOT NULL
	
	)";

	mysqli_query($link,$query);

    $query = "INSERT INTO Output_table (control_output_1,control_output_2,control_output_3,control_output_4,control_output_5,control_output_6) 
		VALUES (0,0,0,0,0,0)"; 

		mysqli_query($link,$query);
}




echo(" voy a poner el query ");
// Post the outputs to the table
mysqli_query($link,$query);
echo(" puse el query ");
	mysqli_close($link);

   	//header("Location: index.php");
echo $str;
?>

