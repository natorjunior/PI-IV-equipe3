<?php 
session_start(); 
if(!empty($_SESSION['user_id'])) header('Location: feed.php'); 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Peneirada</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
<header class="topbar">
  <div class="wrap">
    <!-- Logo à esquerda -->
    <div class="brand">
      <a href="index.php">
        <img src="../imagens/logo.png" alt="Logo Peneirada">
      </a>
    </div>

    <!-- Botões à direita -->
    <nav class="menu">
      <a href="login.php" class="btn">Login</a>
      <a href="cadastro.php" class="btn btn-outline">Register</a>
    </nav>
  </div>
</header>
<!-- CONTEÚDO CENTRAL -->
<main class="center">
  <div class="card small">

    <!-- Título -->
    <h1>Seja bem-vindo</h1>
    <p class="subtitle">Adicione suas credenciais nos campos abaixo</p>

    <!-- Formulário -->
    <form id="loginForm">
      <label for="email">Email*</label>
      <input type="email" id="email" placeholder="Digite seu email" required>

      <label for="password">Senha*</label>
      <input type="password" id="password" placeholder="Digite sua senha" required>

      <button class="btn" type="submit" id="botaoLogin">Login</button>
    </form>

    <!-- Footer -->
  </div>
  <footer>
  <p id="footer">© 2025 Peneirada FC</p>
</footer>
</main>

<!-- SCRIPT LOGIN -->
<script src="main.js"></script>
<script>
document.getElementById('loginForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  const res = await fetch('api.php?action=login', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({email,password})
  });
  const j = await res.json();
  alert(j.msg);
  if(j.status === 'ok') location.href='feed.php';
});
</script>

</body>
</html>
