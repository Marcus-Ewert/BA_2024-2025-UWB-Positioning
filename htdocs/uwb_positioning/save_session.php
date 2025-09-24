<?php
require_once "conn.php";

if (isset($_GET['anchor_setup_id'])) {
    $anchor_setup_id = $_GET['anchor_setup_id'];

    $prep = $conn->prepare("INSERT INTO `sessions`(`anchor_setup_id`) VALUES (?)");
    $prep->execute([$anchor_setup_id]);

    $session_id = $conn->query("SELECT id FROM sessions ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC)['id'];
    
    $prep = $conn->query(
        "INSERT INTO `session_measurements`(`session_id`, `measurement_id`, `timestamp_server`, `runtime_arduino`, `dist_anchor_1`, `dist_anchor_2`, `dist_anchor_3`, `dist_anchor_4`)
        SELECT $session_id, `id`, `timestamp_server`, `runtime_arduino`, `dist_anchor_1`, `dist_anchor_2`, `dist_anchor_3`, `dist_anchor_4`
        FROM `measurements` WHERE 1");
}

echo "You called " . __FILE__ . "<br>";
?>
