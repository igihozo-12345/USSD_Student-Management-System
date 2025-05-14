<?php
$host = 'localhost';
$db = 'student_ussd';
$user = 'root';
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
} catch (PDOException $e) {
    exit("DB connection failed: " . $e->getMessage());
}
?>
