<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - Peneirada</title>
    
    <link rel="stylesheet" href="feed.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <header class="header">
        <div class="header-left">
            <img src="../imagens/logo.png" alt="Logo" class="logo"> 
        </div>

        <div class="header-center">
            <input type="text" placeholder="Search" class="search-input">
        </div>

        <div class="header-right">
            <div class="user-profile" id="userProfile">
                <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? '../imagens/default_avatar.png'); ?>" alt="User" class="avatar">
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?></span>
                    <span class="user-handle">@<?php echo htmlspecialchars($_SESSION['user_handle'] ?? 'usuario'); ?></span>
                </div>
                <div class="dropdown" id="dropdownMenu">
                    <a href="profile.php">Meu perfil</a>
                    <a href="#">Configurações</a>
                    <a href="#" id="logoutLink">Sair</a> 
                </div>
            </div>
            <button class="icon-button"><i class="fa fa-cog"></i></button>
            <button class="icon-button"><i class="fa fa-bell"></i></button>
        </div>
    </header>

    <main class="main-grid">
        <aside class="sidebar">
            <div class="user-box">
                <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? '../imagens/default_avatar.png'); ?>" alt="User" class="avatar">
                <h3><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?></h3>
            </div>
            <nav class="menu">
                <p class="menu-title">Painel de Controle</p>
                <a href="profile.php" class="menu-item"><i class="fa fa-user"></i> Meu Perfil</a>
                <a href="#" class="menu-item"><i class="fa fa-users"></i> Ver Amigos</a>
                <a href="#" class="menu-item"><i class="fa fa-chart-line"></i> Análise de usuário</a>
                <a href="#" class="menu-item"><i class="fa fa-cog"></i> Configurações</a>
                <a href="#" class="menu-item"><i class="fa fa-shield-alt"></i> Security data</a>
                <a href="#" class="menu-item logout"><i class="fa fa-sign-out-alt"></i> Sair</a>
            </nav>
        </aside>

        <section class="feed">
            <div class="card">
                <form id="postForm" enctype="multipart/form-data">
                    <textarea name="text" id="text" rows="3" placeholder="Faça um Post agora !"></textarea>
                    
                    <div class="post-options">
                        <label for="image" id="imageLabel" class="option-label">
                            <i class="fa fa-image"></i> Adicionar Imagem
                        </label>
                        <input type="file" name="image" id="image" accept="image/*" style="display: none;">
                        <button class="btn" type="submit">Publicar</button>
                    </div>
                </form>
            </div>

            <div id="posts"></div>
        </section>
    </main>

    <script>
        // =======================================================
        // DECLARAÇÕES DE CONSTANTES E VARIÁVEIS
        // =======================================================
        const LOGGED_IN_USER_ID = <?php echo json_encode($_SESSION['user_id']); ?>;
        const userProfile = document.getElementById('userProfile');
        const dropdownMenu = document.getElementById('dropdownMenu');
        const logoutLink = document.getElementById('logoutLink');
        const postForm = document.getElementById('postForm');
        const postsContainer = document.getElementById('posts');
        const imageInput = document.getElementById('image');
        const imageLabel = document.getElementById('imageLabel');

        // =======================================================
        // FEEDBACK AO SELECIONAR IMAGEM
        // =======================================================
        if (imageInput && imageLabel) {
            imageInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    imageLabel.innerHTML = '<i class="fa fa-check-circle"></i> Imagem selecionada!';
                    imageLabel.classList.add('selected');
                } else {
                    imageLabel.innerHTML = '<i class="fa fa-image"></i> Adicionar Imagem';
                    imageLabel.classList.remove('selected');
                }
            });
        }

        // =======================================================
        // ESCAPE HTML
        // =======================================================
        function escapeHtml(text) { 
            if (!text) return ''; 
            return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); 
        }

        // =======================================================
        // CARREGAR FEED
        // =======================================================
        async function loadFeed() {
            if (!postsContainer) return;
            try {
                const res = await fetch('api.php?action=list_posts');
                if (!res.ok) throw new Error(`Falha na API: ${res.status}`);
                const j = await res.json();
                postsContainer.innerHTML = '';

                if (j.status === 'ok' && Array.isArray(j.posts)) {
                    j.posts.forEach(function(p) {
                        const div = document.createElement('div');
                        div.className = 'card post';

                        let deleteButtonHtml = '';
                        if (p.user_id === LOGGED_IN_USER_ID) {
                            deleteButtonHtml = `<button class="delete-btn" data-postid="${p.id}">&times;</button>`;
                        }

                        const userNameHtml = `
                            <div class="post-header">
                                <strong class="user-name">${p.user_name || 'Usuário'}</strong>
                                ${deleteButtonHtml}
                            </div>
                        `;
                        const imgHtml = p.image ? `
                            <div class="post-image"><img src="${p.image}" alt="Imagem do post"></div>
                        ` : '';

                        div.innerHTML = `${userNameHtml}
                            <div class="post-content">
                                <p>${escapeHtml(p.content)}</p>
                                ${imgHtml}
                            </div>`;
                        postsContainer.appendChild(div);
                    });
                } else {
                    postsContainer.innerHTML = '<p style="text-align: center; color: #777;">Nenhum post encontrado.</p>';
                }
            } catch (error) {
                console.error('Erro ao carregar o feed:', error);
                postsContainer.innerHTML = '<p style="text-align: center; color: #777;">Erro ao carregar o feed.</p>';
            }
        }

        // =======================================================
        // EVENTOS: DROPDOWN, LOGOUT, POSTAR, DELETAR
        // =======================================================
        if (userProfile) {
            userProfile.addEventListener('click', (e) => {
                e.stopPropagation(); 
                dropdownMenu.style.display = dropdownMenu.style.display === 'flex' ? 'none' : 'flex';
            });
        }

        document.addEventListener('click', (e) => {
            if (userProfile && !userProfile.contains(e.target)) dropdownMenu.style.display = 'none';
        });

        if (logoutLink) {
            logoutLink.addEventListener('click', async function(e) {
                e.preventDefault();
                await fetch('api.php?action=logout');
                location.href = 'index.php';
            });
        }

        if (postForm) {
            postForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const fd = new FormData();
                fd.append('text', postForm.querySelector('textarea[name="text"]').value.trim());
                const imageInput = postForm.querySelector('input[type="file"][name="image"]');
                if (imageInput && imageInput.files.length > 0) {
                    fd.append('image', imageInput.files[0]);
                }

                try {
                    const res = await fetch('api.php?action=create_post', { method: 'POST', body: fd });
                    const j = await res.json();
                    alert(j.msg);
                    if (j.status === 'ok') {
                        postForm.reset();
                        imageLabel.innerHTML = '<i class="fa fa-image"></i> Adicionar Imagem';
                        imageLabel.classList.remove('selected');
                        loadFeed();
                    }
                } catch (error) {
                    console.error('Erro ao criar post:', error);
                    alert('Erro ao conectar com o servidor.');
                }
            });
        }

        if (postsContainer) {
            postsContainer.addEventListener('click', async function(e) {
                if (e.target && e.target.classList.contains('delete-btn')) {
                    if (!confirm('Tem certeza que deseja excluir este post?')) return;
                    const postId = e.target.getAttribute('data-postid');
                    const fd = new FormData();
                    fd.append('post_id', postId);
                    try {
                        const res = await fetch('api.php?action=delete_post', { method: 'POST', body: fd });
                        const j = await res.json();
                        alert(j.msg);
                        if (j.status === 'ok') loadFeed();
                    } catch (error) {
                        console.error('Erro ao deletar post:', error);
                        alert('Erro ao conectar com o servidor.');
                    }
                }
            });
        }

        loadFeed();
    </script>
</body>
</html>
