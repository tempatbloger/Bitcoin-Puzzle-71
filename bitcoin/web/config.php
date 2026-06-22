<?php
// config.php
$host = '127.0.0.1';
$dbname = 'bitcoin_analysis';
$user = 'bitcoin';
$password = 'bitcoin123';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>