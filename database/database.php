<?php

$host = "localhost";
$dbname = "bewegingsapp";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    echo "Verbonden met MySQL!";
} catch (PDOException $e) {
    echo "Verbinding mislukt: " . $e->getMessage();
}
?>