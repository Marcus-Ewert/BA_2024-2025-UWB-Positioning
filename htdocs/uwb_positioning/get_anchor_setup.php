<?php
require_once "conn.php";

if (isset($_GET['anchor_setup'])) {
    
    $setup_id = $_GET['anchor_setup'];

    $sql = "SELECT * FROM `anchor_setups` WHERE id = $setup_id ";
    $res = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);
    if (!$res) {
        echo "error: no measurements found";
        exit;
    }
    echo json_encode($res);
}

?>