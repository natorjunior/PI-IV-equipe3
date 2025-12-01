<?php
require 'config.php'; 
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit; 
}

// ==================================================================
// GRUPO 1: DADOS DE QUEM ESTÁ LOGADO (VOCÊ) - Para Header e Sidebar
// ==================================================================
$myId = (int)$_SESSION['user_id'];
$myName = $_SESSION['user_name'];
// Garante caminho correto para a sidebar
$myAvatar = $_SESSION['user_avatar'] ?? '../imagens/default_avatar.png';
if (strpos($myAvatar, '../') === false && strpos($myAvatar, 'http') === false) {
    $myAvatar = '../' . $myAvatar;
}
$myHandle = $_SESSION['user_handle'] ?? 'usuario';

// ==================================================================
// GRUPO 2: DADOS DO PERFIL QUE ESTAMOS OLHANDO (O ALVO)
// ==================================================================

// Se tem ID na URL, usa ele. Se não, usa o meu ID.
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : $myId;

// Verifica se sou eu mesmo
$isOwnProfile = ($profileId === $myId);

// Variáveis do Perfil (Inicializa com valores padrão)
$pName = '';
$pAvatar = '';
$pCapa = '';
$pBio = '';
$pLoc = '';
$pSobre = '';

if ($isOwnProfile) {
    // --- Se sou eu, pego da sessão ---
    $pName = $myName;
    $pAvatar = $myAvatar;
    $pCapa = $_SESSION['user_capa'] ?? 'https://placehold.co/1200x300/555/999?text=Capa+do+Perfil';
    $pBio = $_SESSION['user_bio'] ?? '';
    $pLoc = $_SESSION['user_localizacao'] ?? 'Brasil';
    $pSobre = $_SESSION['user_sobre'] ?? '';
} else {
    // --- Se é outro usuário, busco no banco ---
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$profileId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $pName = $user['nome'];
        // Ajusta caminhos
        $pAvatar = $user['avatar'] ? '../' . $user['avatar'] : '../imagens/default_avatar.png';
        $pCapa = $user['capa'] ? '../' . $user['capa'] : 'https://placehold.co/1200x300/555/999?text=Capa+do+Perfil';
        $pBio = $user['bio'];
        $pLoc = $user['localizacao'] ?? 'Brasil';
        $pSobre = $user['sobre'];
    } else {
        echo "Usuário não encontrado.";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($pName); ?> - Peneirada</title>
    
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="feed.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <header class="header">
        <div class="header-left">
            <a href="feed.php">
                <img src="../imagens/logo.png" alt="Logo" class="logo"> 
            </a>
        </div>
        <div class="header-center">
            <input type="text" placeholder="Search" class="search-input">
        </div>
        <div class="header-right">
            <div class="user-profile" id="userProfile">
                <img src="<?php echo htmlspecialchars($myAvatar); ?>" alt="User" class="avatar">
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
                <img src="<?php echo htmlspecialchars($myAvatar); ?>" alt="User" class="avatar">
                <h3><?php echo htmlspecialchars($myName); ?></h3>
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

        <section class="feed profile-content-area">
            
            <div class="profile-banner" style="background-image: url('<?php echo htmlspecialchars($pCapa); ?>');">
                <div class="banner-actions">
                    <?php if ($isOwnProfile): ?>
                        <button class="btn-banner" id="openEditModalBtn"><i class="fa fa-edit"></i> EDITAR PERFIL</button>
                    <?php endif; ?>
                    </div>
            </div>

            <div class="card profile-header-card">
                <div class="profile-header-flex">
                    
                    <div class="avatar-container-styled">
                        <div class="avatar-shadow-box"></div>
                        
                        <?php if ($isOwnProfile): ?>
                            <form id="avatarUploadForm" enctype="multipart/form-data">
                                <label for="newAvatarInput" class="avatar-upload-label clickable-avatar" title="Clique para alterar a foto">
                                    <img src="<?php echo htmlspecialchars($pAvatar); ?>" alt="FOTO" class="profile-avatar-styled" id="currentAvatarDisplay">
                                </label>
                                <input type="file" name="avatar" id="newAvatarInput" accept="image/*" style="display: none;">
                            </form>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($pAvatar); ?>" alt="FOTO" class="profile-avatar-styled">
                        <?php endif; ?>
                    </div>

                    <div class="user-details-styled">
                        <div class="user-title-row">
                            <h1><?php echo htmlspecialchars($pName); ?></h1>
                            <span class="user-location">
                                <i class="fa fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($pLoc ?? ''); ?>
                            </span>
                        </div>
                        
                        <p class="user-bio">
                            <?php echo htmlspecialchars($pBio ?? ''); ?>
                        </p>

                        <div class="profile-action-buttons">
                            <button class="btn-teal" onclick="openConnectionsModal(<?php echo $profileId; ?>)">
                                <span id="connectionsCount">0</span> CONEXÕES
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card profile-about-card">
                <h3>Sobre</h3>
                <p>
                    <?php 
                        if (!empty($pSobre)) {
                            echo nl2br(htmlspecialchars($pSobre)); 
                        } else {
                            echo '<span style="color: #999;">Sem informações adicionais.</span>';
                        }
                    ?>
                </p>
            </div>

            <h3 style="margin: 30px 0 15px 0; color: #333; padding-left: 10px; border-left: 4px solid #3AD8C5;">
                Publicações
            </h3>
            
            <div id="profilePostsContainer">
                <p style="text-align: center; color: #888; padding: 20px;">Carregando publicações...</p>
            </div>

        </section>
    </main>
        </section>
    </main>

    <?php if ($isOwnProfile): ?>
    <div id="editProfileModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Perfil</h2>
                <span class="close-modal">&times;</span>
            </div>
            <form id="editProfileForm" enctype="multipart/form-data">
                <div class="form-group">
                <label style="margin-bottom: 10px; display: block;">Alterar Capa</label>
                
                <label for="capaInput" class="custom-file-upload">
                    <i class="fa fa-camera"></i> Escolher Imagem
                </label>
                
                <input type="file" name="capa" id="capaInput" accept="image/*" style="display: none;">
                
                <span id="capaFileName" style="font-size: 12px; color: #666; margin-left: 10px;">Nenhum arquivo selecionado</span>
                
                <div style="margin-top: 5px;">
                    <small>Recomendado: <br>1200x300 pixels</small>
                </div>
            </div>
                <div class="form-group">
                    <label>Localização</label>
                    <input type="text" name="localizacao" value="<?php echo htmlspecialchars($pLoc ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Bio Curta (Manchete)</label>
                    <input type="text" name="bio" value="<?php echo htmlspecialchars($pBio ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Sobre (Resumo Completo)</label>
                    <textarea name="sobre" rows="5"><?php echo htmlspecialchars($pSobre ?? ''); ?></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel close-modal-btn">Cancelar</button>
                    <button type="submit" class="btn-save">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div id="connectionsModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Conexões</h2>
                <span class="close-modal" onclick="closeConnectionsModal()">&times;</span>
            </div>
            <div id="friendsListContainer" class="friends-list-box">
                <p>Carregando...</p>
            </div>
        </div>
    </div>

    <script>
        // IDs e Dados do PHP para o JS
        const IS_OWN_PROFILE = <?php echo $isOwnProfile ? 'true' : 'false'; ?>;
        const PROFILE_ID = <?php echo $profileId; ?>;

        // --- Lógica da Barra de Busca (Global) ---
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    const term = searchInput.value.trim();
                    if (term) window.location.href = `search.php?q=${encodeURIComponent(term)}`;
                }
            });
        }

        // --- Dropdown e Logout ---
        const userProfile = document.getElementById('userProfile');
        const dropdownMenu = document.getElementById('dropdownMenu');
        if (userProfile) {
            userProfile.addEventListener('click', (e) => {
                e.stopPropagation(); 
                dropdownMenu.style.display = dropdownMenu.style.display === 'flex' ? 'none' : 'flex';
            });
        }
        document.addEventListener('click', (e) => {
            if (userProfile && !userProfile.contains(e.target)) dropdownMenu.style.display = 'none';
        });

        const logoutLink = document.getElementById('logoutLink');
        if (logoutLink) {
            logoutLink.addEventListener('click', async (e) => {
                e.preventDefault();
                await fetch('api.php?action=logout');
                location.href = 'index.php';
            });
        }

        // --- Lógica de Edição (Só se for o dono) ---
        if (IS_OWN_PROFILE) {
            // Upload Avatar
            const avatarInput = document.getElementById('newAvatarInput');
            const avatarForm = document.getElementById('avatarUploadForm');
            if (avatarInput) {
                avatarInput.addEventListener('change', async function() {   
                    if (avatarInput.files.length > 0) {       
                        const formData = new FormData(avatarForm);
                        try {
                            const res = await fetch('api.php?action=update_avatar', { method: 'POST', body: formData });
                            const j = await res.json();
                            alert(j.msg);
                            if (j.status === 'ok') location.reload();
                        } catch (error) {
                            alert('Erro de rede ao atualizar foto.');
                        }
                    }
                });
            }

            // Modal Editar Perfil
            const modal = document.getElementById('editProfileModal');
            const openBtn = document.getElementById('openEditModalBtn');
            const closeBtns = document.querySelectorAll('.close-modal, .close-modal-btn');
            const editForm = document.getElementById('editProfileForm');

            if (openBtn && modal) {
                openBtn.addEventListener('click', () => modal.style.display = 'flex');
                closeBtns.forEach(btn => btn.addEventListener('click', () => modal.style.display = 'none'));
                window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });

                if (editForm) {
                    editForm.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const formData = new FormData(editForm);
                        try {
                            const res = await fetch('api.php?action=update_profile', { method: 'POST', body: formData });
                            const j = await res.json();
                            alert(j.msg);
                            if (j.status === 'ok') location.reload();
                        } catch (error) {
                            alert('Erro ao salvar perfil.');
                        }
                    });
                }
            }
        }

        // --- Lógica de Conexões ---
        const connModal = document.getElementById('connectionsModal');
        const countSpan = document.getElementById('connectionsCount');
        const listContainer = document.getElementById('friendsListContainer');

        async function fetchConnections() {
            const fd = new FormData();
            fd.append('user_id', PROFILE_ID); // Busca amigos do perfil que estamos VENDO

            try {
                const res = await fetch('api.php?action=list_friends', { method: 'POST', body: fd });
                const j = await res.json();
                if (j.status === 'ok') {
                    if(countSpan) countSpan.innerText = j.amigos.length;
                    window.currentFriends = j.amigos; // Guarda lista
                }
            } catch (error) { console.error(error); }
        }

        function openConnectionsModal() {
            connModal.style.display = 'flex';
            listContainer.innerHTML = '';
            const friends = window.currentFriends || [];

            if (friends.length === 0) {
                listContainer.innerHTML = '<p style="padding:20px; text-align:center; color:#777">Nenhuma conexão encontrada.</p>';
            } else {
                friends.forEach(f => {
                    const div = document.createElement('div');
                    div.className = 'friend-item';
                    
                    // AQUI ESTÁ A ESTRUTURA CORRETA
                    div.innerHTML = `
                        <a href="profile.php?id=${f.id}" class="friend-link">
                            <img src="${f.avatar}" alt="${f.nome}">
                            <div class="friend-info">
                                <h4>${f.nome}</h4>
                                <span>${f.bio ? f.bio.substring(0, 30) + '...' : ''}</span>
                            </div>
                        </a>
                    `;
                    listContainer.appendChild(div);
                });
            }
        }

        function closeConnectionsModal() {
            connModal.style.display = 'none';
        }
        
        // Carrega número de amigos ao iniciar
        fetchConnections();

        // --- Lógica de Carregar Posts do Perfil ---
        const profilePostsContainer = document.getElementById('profilePostsContainer');

        async function loadProfilePosts() {
            const fd = new FormData();
            fd.append('user_id', PROFILE_ID); // PROFILE_ID já foi definido lá no topo do script PHP

            try {
                const res = await fetch('api.php?action=list_posts', { method: 'POST', body: fd });
                const j = await res.json();

                profilePostsContainer.innerHTML = '';

                if (j.status === 'ok' && j.posts.length > 0) {
                    j.posts.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'card post'; // Usa o mesmo estilo do feed principal

                        // Verifica se tem imagem
                        const imgHtml = p.image ? `
                            <div class="post-image" style="height: 300px;">
                                <img src="${p.image}" alt="Imagem do post" style="object-fit: cover; width: 100%; height: 100%;">
                            </div>` : '';

                        // Botão de excluir (só se for meu perfil e meu post)
                        let deleteBtn = '';
                        if (IS_OWN_PROFILE) {
                            deleteBtn = `<button class="delete-btn" onclick="deleteProfilePost(${p.id})" style="float:right; border:none; background:none; cursor:pointer; font-size:20px; color:#aaa;">&times;</button>`;
                        }

                        // Sistema de curtidas
                        const isLiked = p.user_liked > 0;
                        const likeCount = p.like_count || 0;
                        const likeClass = isLiked ? 'liked' : '';
                        const likeIcon = isLiked ? 'fa-solid fa-heart' : 'fa-regular fa-heart';

                        div.innerHTML = `
                            <div class="post-header" style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <img src="${p.avatar}" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                    <div>
                                        <strong>${p.nome}</strong><br>
                                        <small style="color:#888; font-size:12px;">${p.created_at}</small>
                                    </div>
                                </div>
                                ${deleteBtn}
                            </div>
                            <p>${p.content}</p>
                            ${imgHtml}
                            <div class="post-actions">
                                <button class="like-btn ${likeClass}" data-postid="${p.id}">
                                    <i class="${likeIcon}"></i> <span class="like-count">${likeCount}</span>
                                </button>
                            </div>
                        `;
                        profilePostsContainer.appendChild(div);
                    });
                } else {
                    profilePostsContainer.innerHTML = '<p style="text-align: center; padding: 20px; color: #777;">Nenhuma publicação ainda.</p>';
                }
            } catch (error) {
                console.error(error);
                profilePostsContainer.innerHTML = '<p>Erro ao carregar posts.</p>';
            }
        }

        // Função extra para deletar post direto do perfil
        async function deleteProfilePost(id) {
            if(!confirm('Excluir este post?')) return;
            
            const fd = new FormData();
            fd.append('post_id', id);
            const res = await fetch('api.php?action=delete_post', {method:'POST', body:fd});
            const j = await res.json();
            if(j.status === 'ok') {
                loadProfilePosts(); // Recarrega a lista
            } else {
                alert(j.msg);
            }
        }

        // Event listener para curtidas nos posts do perfil
        if (profilePostsContainer) {
            profilePostsContainer.addEventListener('click', async function(e) {
                if (e.target && (e.target.classList.contains('like-btn') || e.target.closest('.like-btn'))) {
                    const likeBtn = e.target.classList.contains('like-btn') ? e.target : e.target.closest('.like-btn');
                    const postId = likeBtn.getAttribute('data-postid');
                    
                    likeBtn.disabled = true;
                    
                    const fd = new FormData();
                    fd.append('post_id', postId);
                    
                    try {
                        const res = await fetch('api.php?action=toggle_like', { method: 'POST', body: fd });
                        const j = await res.json();
                        
                        if (j.status === 'ok') {
                            const icon = likeBtn.querySelector('i');
                            const countSpan = likeBtn.querySelector('.like-count');
                            
                            if (j.liked) {
                                likeBtn.classList.add('liked');
                                icon.className = 'fa-solid fa-heart';
                            } else {
                                likeBtn.classList.remove('liked');
                                icon.className = 'fa-regular fa-heart';
                            }
                            
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

        // Chama a função ao carregar a página
        loadProfilePosts();
        // --- Lógica Visual do Input de Capa ---
        const capaInput = document.getElementById('capaInput');
        const capaFileName = document.getElementById('capaFileName');

        if (capaInput && capaFileName) {
            capaInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    // Mostra o nome do arquivo selecionado
                    capaFileName.textContent = this.files[0].name;
                    capaFileName.style.color = "#3AD8C5"; // Fica verde para confirmar
                    capaFileName.style.fontWeight = "bold";
                } else {
                    capaFileName.textContent = "Nenhum arquivo selecionado";
                    capaFileName.style.color = "#666";
                }
            });
        }

    </script>
</body>
</html>