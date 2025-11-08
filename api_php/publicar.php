<?php
session_start();
include('conexao.php');
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conteudo = $_POST['conteudo'] ?? '';
    $imagePath = null;
    if (!empty($_FILES['imagem']['tmp_name'])) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $filename = 'post_' . time() . '.' . $ext;
        $target = __DIR__ . '/uploads/' . $filename;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $target)) {
            $imagePath = $filename;
        }
    }
    $usuario_id = $_SESSION['usuario'];
    $stmt = $conn->prepare('INSERT INTO post (usuario_id, conteudo, imagem) VALUES (?,?,?)');
    $stmt->bind_param('iss', $usuario_id, $conteudo, $imagePath);
    $stmt->execute();
}
header('Location: home.php');
exit;
?>