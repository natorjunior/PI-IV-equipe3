<?php
$host = 'mysql';
$user = 'peneirauser';
$pass = 'peneira123';
$db   = 'peneirada';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Erro de conexão: ' . $conn->connect_error);
}
?>