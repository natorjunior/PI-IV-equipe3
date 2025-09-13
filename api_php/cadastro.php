<?php session_start(); if(!empty($_SESSION['user_id'])) header('Location: feed.php'); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cadastro - Peneirada</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar"><div class="wrap"><div class="brand">Peneirada</div>
<nav class="menu"><a href="index.php">Início</a><a href="login.php">Login</a></nav></div></header>

<main class="center">
  <div class="card small">
    <h2>Cadastro de Atleta</h2>
    <form id="registerForm" enctype="multipart/form-data">
      <label>Nome completo</label>
      <input type="text" name="name" id="name" required>
      <label>Email</label>
      <input type="email" name="email" id="email" required>
      <label>Senha</label>
      <input type="password" name="password" id="password" required>
      <label>Data de nascimento</label>
      <input type="date" name="nascimento" id="nascimento" required>
      <label>Foto (avatar)</label>
      <input type="file" name="avatar" id="avatar" accept="image/*">
      <button class="btn" type="submit">Criar conta</button>
    </form>
    <p class="muted">Idade mínima: 16 anos.</p>
  </div>
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
