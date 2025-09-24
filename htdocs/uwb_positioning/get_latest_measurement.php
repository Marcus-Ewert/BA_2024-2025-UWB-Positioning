<?php
require_once "conn.php";

$id = 1;

$sql = "SELECT * FROM `measurements` ORDER BY id DESC LIMIT 1";
$res = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);
if (!$res) {
    echo "error: no measurements found";
    exit;
}
echo json_encode($res);


?>