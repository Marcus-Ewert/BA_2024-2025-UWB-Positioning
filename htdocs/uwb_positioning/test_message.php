<?php
require_once "conn.php";

if (isset($_GET['message'])) {
    $msg = $_GET['message'];
    echo "message received: $msg<br>";
    $prep = $conn->prepare("INSERT INTO test_messages (`message`) VALUES (?)");
    $prep->execute([$msg]);
}

echo "You called " . __FILE__ . "<br>";
?>
