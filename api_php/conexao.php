<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // deixe vazio se usa senha no XAMPP
$db   = 'peneirada';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Erro de conexão: ' . $conn->connect_error);
}
?>