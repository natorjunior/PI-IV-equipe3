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
                    <span class="username">Usuário:<br><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?></span>
                </div>
                <div class="dropdown" id="dropdownMenu">
                    <a href="profile.php">Meu perfil</a>
                    <a href="#" id="logoutLink">Sair</a> 
                </div>
            </div>
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
                <a href="feed.php" class="menu-item"><i class="fa fa-home"></i> Feed</a>
                <a href="profile.php" class="menu-item"><i class="fa fa-user"></i> Meu Perfil</a>
                <a href="search.php" class="menu-item"><i class="fa fa-users"></i> Buscar Usuários</a>
                <!-- <a href="#" class="menu-item"><i class="fa fa-chart-line"></i> Análise de usuário</a>
                <a href="#" class="menu-item"><i class="fa fa-cog"></i> Configurações</a>
                <a href="#" class="menu-item"><i class="fa fa-shield-alt"></i> Security data</a>
                <a href="#" class="menu-item logout"><i class="fa fa-sign-out-alt"></i> Sair</a> -->
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
        // --- Lógica da Barra de Busca (Enter para pesquisar) ---
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    const term = searchInput.value.trim();
                    if (term) {
                        // Redireciona para a página de busca enviando o termo na URL
                        window.location.href = `search.php?q=${encodeURIComponent(term)}`;
                    }
                }
            });
        }
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

         // Função unificada para Adicionar ou Remover
        async function toggleFriend(btn, amigoId, isAdding) {
            // Desabilita o botão para evitar cliques duplos
            btn.disabled = true;
            
            const action = isAdding ? 'add_friend' : 'remove_friend';
            const fd = new FormData();
            fd.append('amigo_id', amigoId);

            try {
                const res = await fetch(`api.php?action=${action}`, { method: 'POST', body: fd });
                const j = await res.json();
                
                if (j.status === 'ok') {
                    // Recarrega o feed para atualizar todos os botões desse usuário na tela
                    loadFeed(); 
                } else {
                    alert(j.msg);
                    btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                btn.disabled = false;
            }
        }

        // Função loadFeed ATUALIZADA
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

                        // 1. Botão Excluir (Se for meu post)
                        let deleteButtonHtml = '';
                        if (p.user_id === LOGGED_IN_USER_ID) {
                            deleteButtonHtml = `<button class="delete-btn" data-postid="${p.id}">&times;</button>`;
                        }

                        // 2. Botão de Amigo (Se NÃO for meu post)
                        let friendBtnHtml = '';
                        if (p.user_id !== LOGGED_IN_USER_ID) {
                            
                            // Verifica se o campo is_friend veio como 1 (true) ou 0 (false)
                            const isFriend = p.is_friend > 0;

                            if (isFriend) {
                                // JÁ É AMIGO: Botão Vermelho "Remover"
                                friendBtnHtml = `
                                    <button class="btn-friend remove" onclick="toggleFriend(this, ${p.user_id}, false)">
                                        <i class="fa fa-user-minus"></i> Remover Amigo
                                    </button>
                                `;
                            } else {
                                // NÃO É AMIGO: Botão Verde "Adicionar"
                                friendBtnHtml = `
                                    <button class="btn-friend" onclick="toggleFriend(this, ${p.user_id}, true)">
                                        <i class="fa fa-user-plus"></i> Adicionar
                                    </button>
                                `;
                            }
                        }

                        const avatar = p.avatar ? `<img src="${p.avatar}" class="small-avatar">` : '<div class="small-avatar placeholder"></div>';
                        const imgHtml = p.image ? `<div class="post-image"><img src="${p.image}"></div>` : '';

                        // 3. Botão de Curtida
                        const isLiked = p.user_liked > 0;
                        const likeCount = p.like_count || 0;
                        const likeClass = isLiked ? 'liked' : '';
                        const likeIcon = isLiked ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
                        
                        // --- MONTAGEM DO HTML DO POST (ATUALIZADO) ---
                        div.innerHTML = `
                            <div class="post-header">
                                <div class="post-header-left" style="display: flex; align-items: center; gap: 10px;">
                                    
                                    <img src="${p.avatar}" class="small-avatar" alt="Avatar" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                    
                                    <div style="display:flex; flex-direction:column; line-height: 1.2;">
                                        <strong class="user-name" style="font-size: 14px; margin:0;">${escapeHtml(p.nome || 'Usuário')}</strong>
                                        <small style="color:#888; font-size:11px;">${p.created_at}</small>
                                    </div>

                                    <div style="margin-left: 5px;">
                                        ${friendBtnHtml}
                                    </div>
                                </div>
                                
                                ${deleteButtonHtml}
                            </div>

                            <div class="post-content">
                                <p>${escapeHtml(p.content)}</p>
                                ${imgHtml}
                            </div>

                            <div class="post-actions">
                                <button class="like-btn ${likeClass}" data-postid="${p.id}">
                                    <i class="${likeIcon}"></i> <span class="like-count">${likeCount}</span>
                                </button>
                            </div>
                        `;
                        
                        postsContainer.appendChild(div);
                    });
                } else {
                    postsContainer.innerHTML = '<p style="text-align: center; color: #777;">Nenhum post encontrado.</p>';
                }
            } catch (error) {
                console.error('Erro ao carregar o feed:', error);
            }
        }

        // Função para chamar a API e adicionar o amigo
            async function addFriend(amigoId) {
                const fd = new FormData();
                fd.append('amigo_id', amigoId);

                try {
                    const res = await fetch('api.php?action=add_friend', { 
                        method: 'POST', 
                        body: fd 
                    });
                    const j = await res.json();
                    
                    alert(j.msg); // Mostra "Amigo adicionado com sucesso!" ou erro
                    
                } catch (error) {
                    console.error('Erro ao adicionar amigo:', error);
                    alert('Erro de conexão.');
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
                // Handle delete button
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
                
                // Handle like button
                if (e.target && (e.target.classList.contains('like-btn') || e.target.closest('.like-btn'))) {
                    const likeBtn = e.target.classList.contains('like-btn') ? e.target : e.target.closest('.like-btn');
                    const postId = likeBtn.getAttribute('data-postid');
                    
                    // Desabilita o botão temporariamente
                    likeBtn.disabled = true;
                    
                    const fd = new FormData();
                    fd.append('post_id', postId);
                    
                    try {
                        const res = await fetch('api.php?action=toggle_like', { method: 'POST', body: fd });
                        const j = await res.json();
                        
                        if (j.status === 'ok') {
                            const icon = likeBtn.querySelector('i');
                            const countSpan = likeBtn.querySelector('.like-count');
                            
                            // Atualiza o visual do botão
                            if (j.liked) {
                                likeBtn.classList.add('liked');
                                icon.className = 'fa-solid fa-heart';
                            } else {
                                likeBtn.classList.remove('liked');
                                icon.className = 'fa-regular fa-heart';
                            }
                            
                            // Atualiza o contador
                            countSpan.textContent = j.count;
                        } else {
                            alert(j.msg);
                        }
                    } catch (error) {
                        console.error('Erro ao curtir post:', error);
                        alert('Erro ao conectar com o servidor.');
                    } finally {
                        likeBtn.disabled = false;
                    }
                }
            });
        }

        loadFeed();
    </script>
</body>
</html>
