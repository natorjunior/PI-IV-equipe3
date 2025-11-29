<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Peneirada - Rede de Olheiros</title>
  <meta name="description" content="Rede social para peneiradas, atletas e olheiros. Encontre oportunidades, publique treinos e conecte-se com clubes.">
  <link rel="canonical" href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http'); ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/api_php/index.php">
  <meta property="og:title" content="Peneirada FC – Início">
  <meta property="og:description" content="Rede social para peneiradas, atletas e olheiros.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http'); ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/api_php/index.php">
  <meta property="og:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http'); ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/api_php/assets/logo.svg">
  <meta property="og:site_name" content="Peneirada FC">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Peneirada FC – Início">
  <meta name="twitter:description" content="Rede social para peneiradas, atletas e olheiros.">
  <meta name="twitter:image" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http'); ?>://<?php echo $_SERVER['HTTP_HOST']; ?>/api_php/assets/logo.svg">
  <link rel="icon" type="image/svg+xml" href="assets/sieve-icon.svg">
  <link rel="stylesheet" href="index.css">
</head>
<body>
  <header class="topbar">
    <div class="wrap">
      <div class="brand">
        <img src="" alt="">
      </div>
      <nav class="menu">
        <?php if(!empty($_SESSION['user_id'])): ?>
          <a href="feed.php">Feed</a>
          <a href="#" id="logoutBtn">Sair</a>
        <?php else: ?>
        <?php endif; ?>
        <a href="sobre.php">Sobre</a>
      </nav>
    </div>
  </header>

  <main class="hero">
    <div class="card">
      <div class="logo">
        <img src="../imagens/logo.png" alt="Logo Peneirada" width="120" height="160" onerror="this.src='https://via.placeholder.com/120x160?text=Logo+Não+Carregada';">
      </div>
      <h1>Bem-vindo à Peneirada</h1>
      <h3>Seu Olheiro Digital</h3>
      <div class="actions">
        <a class="btn" href="cadastro.php">Começar agora</a>
        <a class="btn" href="login.php">Já tenho conta</a>
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