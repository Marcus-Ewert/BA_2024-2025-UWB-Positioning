<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "uwb_positioning";

try {
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  // echo "<div class='debug'>Connected successfully</div>";
} catch(PDOException $e) {
  // echo "<div class='debug'>Connection failed: " . $e->getMessage() . "</div>";
}
?> 