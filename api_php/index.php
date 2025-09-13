<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Peneirada - Rede de Olheiros</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="topbar">
    <div class="wrap">
      <div class="brand">Peneirada</div>
      <nav class="menu">
        <?php if(!empty($_SESSION['user_id'])): ?>
          <a href="feed.php">Feed</a>
          <a href="#" id="logoutBtn">Sair</a>
        <?php else: ?>
          <a href="login.php">Login</a>
          <a href="cadastro.php">Cadastro</a>
        <?php endif; ?>
        <a href="sobre.php">Sobre</a>
      </nav>
    </div>
  </header>

  <main class="hero">
    <div class="card">
      <div class="logo">
        <img src="assets/logo.svg" alt="Logo Peneirada" width="120" height="160" onerror="this.src='https://via.placeholder.com/120x160?text=Logo+Não+Carregada';">
      </div>
      <h1>Bem-vindo à Peneirada</h1>
      <p>Plataforma que conecta jovens atletas (a partir de 16 anos) a olheiros e clubes. Cadastre-se e mostre seu talento.</p>
      <div class="actions">
        <a class="btn" href="cadastro.php">Começar agora</a>
        <a class="btn" style="background: var(--green); border: none;" href="login.php">Já tenho conta</a>
      </div>
    </div>
  </main>

  <script src="main.js"></script>
  <script>
    document.getElementById('logoutBtn')?.addEventListener('click', async function(e){
      e.preventDefault();
      const res = await fetch('api.php?action=logout');
      const j = await res.json();
      if(j.status === 'ok') location.href = 'index.php';
    });
  </script>
</body>
</html>