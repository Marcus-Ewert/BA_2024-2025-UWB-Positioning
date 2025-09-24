<?php
require_once "conn.php";

$json = file_get_contents('php://input');
$data = json_decode($json, true); 

$sql = 
"INSERT INTO `anchor_setups`(`anchor_1_x`, `anchor_1_y`, `anchor_1_z`, 
`anchor_2_x`, `anchor_2_y`,`anchor_2_z`, 
`anchor_3_x`, `anchor_3_y`, `anchor_3_z`,
`anchor_4_x`, `anchor_4_y`, `anchor_4_z`)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$prep = $conn->prepare($sql);
$prep->execute([
    $data['anchor_1_x'],
    $data['anchor_1_y'],
    $data['anchor_1_z'],
    $data['anchor_2_x'],
    $data['anchor_2_y'],
    $data['anchor_2_z'],
    $data['anchor_3_x'],
    $data['anchor_3_y'],
    $data['anchor_3_z'],
    $data['anchor_4_x'],
    $data['anchor_4_y'],
    $data['anchor_4_z'],
]);


?>
