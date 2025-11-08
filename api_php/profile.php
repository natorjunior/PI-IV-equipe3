<?php
// 1. COMEÇA A SESSÃO E PROTEGE A PÁGINA
// (Exatamente igual ao seu feed.php)
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit; 
}

// 2. BUSCA OS DADOS DO USUÁRIO DA SESSÃO
// (A api.php de login salvou isso quando você logou)
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userAvatar = $_SESSION['user_avatar'] ?? '../imagens/default_avatar.png';
$userId = $_SESSION['user_id'];

// Se você precisar de MAIS dados (ex: email, data de nascimento),
// você faria uma consulta no banco de dados AQUI, usando o $userId.
// Por enquanto, vamos usar apenas os dados da sessão.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Peneirada</title>
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
                <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="User" class="avatar">
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($userName); ?></span>
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
                <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="User" class="avatar">
                <h3><?php echo htmlspecialchars($userName); ?></h3>
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
            
    <div class="card profile-card">
        
        <h1>Meu Perfil</h1>

        <form id="avatarUploadForm" enctype="multipart/form-data">
            <label for="newAvatarInput" class="avatar-upload-label">
                <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="FOTO" class="profile-avatar-large" id="currentAvatarDisplay">
                <br>
                <button type="button" class="btn-secondary" id="editPhotoButton">Editar Foto</button>
                <input type="file" name="avatar" id="newAvatarInput" accept="image/*" style="display: none;">
            </label>
            </form>
            <h2><?php echo htmlspecialchars($userName); ?></h2>

        <p>Nesta aba, no momento, você só pode editar sua foto de perfil !</p>
    </div>
</section>
    </main>

    <script>
        // Script para o dropdown do perfil
        const userProfile = document.getElementById('userProfile');
        const dropdownMenu = document.getElementById('dropdownMenu');
        
        if (userProfile) {
            userProfile.addEventListener('click', (e) => {
                e.stopPropagation(); 
                dropdownMenu.style.display = dropdownMenu.style.display === 'flex' ? 'none' : 'flex';
            });
        }
        
        document.addEventListener('click', (e) => {
            if (userProfile && !userProfile.contains(e.target)) {
                dropdownMenu.style.display = 'none';
            }
        });

        // Script de Logout
        const logoutLink = document.getElementById('logoutLink');
        if (logoutLink) {
            logoutLink.addEventListener('click', async function(e) {
                e.preventDefault();
                await fetch('api.php?action=logout');
                location.href = 'index.php';
            });
        }
        // 1. Selecionamos os elementos do HTML
        const avatarInput = document.getElementById('newAvatarInput');
        const avatarForm = document.getElementById('avatarUploadForm');
        // VVV ADICIONE ESTA LINHA VVV
        const editPhotoButton = document.getElementById('editPhotoButton');

        // =======================================================
        // VVV ADICIONE ESTE BLOCO INTEIRO VVV
        // =======================================================
        // 2. Adicionamos um "ouvinte" ao botão "Editar Foto"
        if (editPhotoButton && avatarInput) {
            editPhotoButton.addEventListener('click', function(e) {
                // e.preventDefault() impede qualquer comportamento padrão do botão
                e.preventDefault(); 
                
                // Explicação:
                // Quando o usuário clica no botão bonito (editPhotoButton),
                // nós usamos JavaScript para "clicar" programaticamente
                // no input de arquivo escondido (avatarInput).
                avatarInput.click();
            });
        }
        // =======================================================
        // FIM DO NOVO BLOCO
        // =======================================================


        // 3. Adicionamos um "ouvinte" ao input de arquivo.
        // (Este código você já tem)
        // O evento "change" dispara no momento em que o usuário SELECIONA um arquivo.
        if (avatarInput) {
            avatarInput.addEventListener('change', async function() {   
                // Verifica se um arquivo foi realmente selecionado
                if (avatarInput.files.length > 0) {       
                    // Empacota os dados do formulário
                    const formData = new FormData(avatarForm);
                    // Envia o arquivo para a API
                    try {
                        const res = await fetch('api.php?action=update_avatar', {
                            method: 'POST', 
                            body: formData   
                        });
                        const j = await res.json();
                        alert(j.msg);
                        if (j.status === 'ok') {
                            location.reload();
                        }
                    } catch (error) {
                        console.error('Erro ao enviar o avatar:', error);
                        alert('Ocorreu um erro de rede. Tente novamente.');
                    }
                }
            });
        }
    </script>
</body>
</html>