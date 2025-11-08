<?php
session_start();
include('conexao.php');
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
$usuario_id = $_SESSION['usuario'];
$nome = $_SESSION['nome'] ?? '';

$result = $conn->query('SELECT p.*, u.nome FROM post p JOIN usuario u ON p.usuario_id = u.id ORDER BY p.data_criacao DESC');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Home - Peneirada</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header class="header">PENEIRADA FC - Bem-vindo, <?php echo htmlspecialchars($nome); ?> <a href="logout.php" class="logout">Sair</a></header>
  <div class="container">
    <aside class="sidebar card">
      <h3>Perfil</h3>
      <?php if (!empty($_SESSION['avatar'])): ?>
        <img src="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" class="avatar">
      <?php endif; ?>
      <p><?php echo htmlspecialchars($nome); ?></p>
    </aside>
    <main class="main-area">
      <div class="card">
        <form method="post" action="publicar.php" enctype="multipart/form-data">
          <textarea name="conteudo" placeholder="No que você está treinando?" required></textarea>
          <input type="file" name="imagem" accept="image/*">
          <button type="submit" class="btn">Publicar</button>
        </form>
      </div>
      <h3>Feed</h3>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="card post">
          <strong><?php echo htmlspecialchars($row['nome']); ?></strong>
          <p><?php echo nl2br(htmlspecialchars($row['conteudo'])); ?></p>
          <?php if ($row['imagem']): ?>
            <img src="uploads/<?php echo htmlspecialchars($row['imagem']); ?>" class="post-image">
          <?php endif; ?>
          <small class="muted"><?php echo $row['data_criacao']; ?></small>
        </div>
      <?php endwhile; ?>
    </main>
  </div>
  <footer class="footer">© Peneirada</footer>
</body>
</html>
