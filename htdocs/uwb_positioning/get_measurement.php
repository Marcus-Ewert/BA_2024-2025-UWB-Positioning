<?php
require_once "conn.php";

$id = 1;

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT * FROM `measurements` WHERE id >= $id ORDER BY id ASC LIMIT 1";
    $res = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);
    if (!$res) {
        echo "error: no measurements found";
        exit;
    }
    echo json_encode($res);
}

?>