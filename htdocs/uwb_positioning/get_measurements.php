<?php
require_once "conn.php";

$sql = "SELECT * FROM `measurements`";
$res = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (!$res) {
    echo "error: no measurements found";
    exit;
}
echo json_encode($res);

?>