<?php
require_once "conn.php";

if (isset($_GET['session_id'])) {
    $session_id = $_GET['session_id'];
    echo $session_id;
    $prep = $conn->prepare(
        "INSERT INTO `measurements`(`id`, `timestamp_server`, `runtime_arduino`, `dist_anchor_1`, `dist_anchor_2`, `dist_anchor_3`, `dist_anchor_4`)
        SELECT `measurement_id`, `timestamp_server`, `runtime_arduino`, `dist_anchor_1`, `dist_anchor_2`, `dist_anchor_3`, `dist_anchor_4`
        FROM `session_measurements` WHERE session_id=?");
    $prep->execute([$session_id]);
}

echo "You called " . __FILE__ . "<br>";
?>