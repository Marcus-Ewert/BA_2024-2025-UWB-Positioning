<?php
require_once "conn.php";

$conn->query("TRUNCATE TABLE measurements");

echo "You called " . __FILE__ . "<br>";
?>
