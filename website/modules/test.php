<?php
$arr = array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5);
$arr["f"] = array(1,2,3);
echo json_encode($arr);

?>