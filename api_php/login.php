<?php session_start(); if(!empty($_SESSION['user_id'])) header('Location: feed.php'); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - Peneirada</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar"><div class="wrap"><div class="brand">Peneirada</div>
<nav class="menu"><a href="index.php">Início</a><a href="cadastro.php">Cadastro</a></nav></div></header>

<main class="center">
  <div class="card small">
    <h2>Entrar</h2>
    <form id="loginForm">
      <label>Email</label>
      <input type="email" id="email" required>
      <label>Senha</label>
      <input type="password" id="password" required>
      <button class="btn" type="submit">Entrar</button>
    </form>
    <p class="muted">Ainda não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
  </div>
</main>

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
  if (j.status === 'ok') location.href = 'feed.php';
});
</script>
</body>
</html>
