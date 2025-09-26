<?php session_start(); if(empty($_SESSION['user_id'])) { header('Location: login.php'); exit; } ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Feed - Peneirada</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar"><div class="wrap"><div class="brand">Peneirada</div>
<nav class="menu">
  <a href="index.php">
    Início
  </a>
  <a href="feed.php">
    Feed
  </a>
  <a href="#" id="logoutLink">
    Sair
  </a>
</nav>
</div>
</header>

<main class="wrap main-grid">
  <aside class="sidebar">
    <div class="profile">
      <?php if(!empty($_SESSION['user_avatar'])): ?>
        <img src="<?php echo htmlspecialchars($_SESSION['user_avatar']); ?>" class="avatar">
      <?php else: ?>
        <div class="avatar placeholder"></div>
      <?php endif; ?>
      <h3><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></h3>
    </div>
  </aside>

  <section class="feed">
    <div class="card">
      <form id="postForm" enctype="multipart/form-data">
        <textarea name="text" id="text" rows="3" placeholder="No que você está treinando?"></textarea>
        <input type="file" name="image" id="image" accept="image/*">
        <button class="btn" type="submit">Publicar</button>
      </form>
    </div>

    <div id="posts"></div>
  </section>
</main>

<script src="main.js"></script>
<script>
document.getElementById('logoutLink').addEventListener('click', async function(e){
  e.preventDefault();
  await fetch('api.php?action=logout');
  location.href = 'index.php';
});

document.getElementById('postForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const form = document.getElementById('postForm');
  const fd = new FormData(form);
  const res = await fetch('api.php?action=create_post', { method:'POST', body: fd });
  const j = await res.json();
  alert(j.msg);
  if (j.status === 'ok') loadFeed();
  form.reset();
});

async function loadFeed(){
  const res = await fetch('api.php?action=list_posts');
  const j = await res.json();
  const container = document.getElementById('posts');
  container.innerHTML = '';
  if(j.status === 'ok'){
    j.posts.forEach(function(p){
      const div = document.createElement('div');
      div.className = 'card post';
      const avatar = p.avatar ? '<img src="'+p.avatar+'" class="small-avatar">' : '<div class="small-avatar placeholder"></div>';
      const imgHtml = p.image ? '<div class="post-image"><img src="'+p.image+'"></div>' : '';
      div.innerHTML = '<div class="post-header">'+avatar+'<div><strong>'+escapeHtml(p.nome)+'</strong><br><small class="muted">'+p.created_at+'</small></div></div><p>'+escapeHtml(p.content)+'</p>'+imgHtml;
      container.appendChild(div);
    });
  }
}
function escapeHtml(text){ if(!text) return ''; return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
loadFeed();
</script>
</body>
</html>
