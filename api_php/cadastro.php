<?php
session_start();
if(!empty($_SESSION['user_id'])) header('Location: feed.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cadastro - Peneirada</title>
  <link rel="stylesheet" href="cadastro.css">
</head>
<body>

<!-- HEADER -->
<header class="topbar">
  <div class="wrap">
    <!-- Logo à esquerda -->
    <div class="brand">
      <img src="../imagens/logo.png" alt="Logo Peneirada">
    </div>

    <!-- Botões à direita -->
    <nav class="menu">
      <a href="login.php" class="btn">Login</a>
      <a href="cadastro.php" class="btn btn-outline">Cadastro</a>
    </nav>
  </div>
</header>

<!-- CONTEÚDO PRINCIPAL -->
<main class="center">
  <div class="card small">
    <h2>Seja bem-vindo</h2>
    <form id="registerForm" enctype="multipart/form-data">
      <label>Nome completo</label>
      <input type="text" name="name" id="name" placeholder="Digite seu nome" required>

      <label>Email</label>
      <input type="email" name="email" id="email" placeholder="Digite seu email" required>

      <label>Senha</label>
      <input type="password" name="password" id="password" placeholder="Digite sua senha" required>

      <label>Data de nascimento</label>
      <input type="date" name="nascimento" id="nascimento" required>

      <label>Foto (Perfil)</label>
      <input type="file" name="avatar" id="avatar" accept="image/*"><br>

      <button class="btn" type="submit">Criar conta</button>
    </form>
    <p class="muted">Idade mínima: 16 anos.</p>
  </div>

  <footer>
    <p id="footer">© 2025 Peneirada FC</p>
  </footer>
</main>

<script src="main.js"></script>
<script>
document.getElementById('registerForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const form = document.getElementById('registerForm');
  const fd = new FormData(form);
  const res = await fetch('api.php?action=register', { method:'POST', body: fd });
  const j = await res.json();
  alert(j.msg);
  if (j.status === 'ok') location.href = 'login.php';
});
</script>
</body>
</html>
