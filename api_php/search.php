<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userAvatar = $_SESSION['user_avatar'] ?? '../imagens/default_avatar.png';
$userName = $_SESSION['user_name'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Busca - Peneirada</title>
    <link rel="stylesheet" href="feed.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos específicos para a lista de resultados */
        .search-title { margin-bottom: 20px; color: #333; }
        .user-result-card {
            display: flex; align-items: center; justify-content: space-between;
            background: #fff; padding: 15px; border-radius: 8px;
            margin-bottom: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .user-result-info { display: flex; align-items: center; gap: 15px; }
        .user-result-info img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .user-result-actions button { font-size: 12px; padding: 5px 10px; }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-left">
            <a href="feed.php"><img src="../imagens/logo.png" alt="Logo" class="logo"></a>
        </div>
        <div class="header-center">
            <input type="text" placeholder="Search" class="search-input">
        </div>
        <div class="header-right">
            <div class="user-profile">
                <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="User" class="avatar">
            </div>
        </div>
    </header>

    <main class="main-grid">
        <aside class="sidebar">
            <nav class="menu">
                <p class="menu-title">Painel de Controle</p>
                <a href="feed.php" class="menu-item"><i class="fa fa-home"></i> Feed</a>
                <a href="profile.php" class="menu-item"><i class="fa fa-user"></i> Meu Perfil</a>
                <!-- <a href="#" class="menu-item"><i class="fa fa-chart-line"></i> Análise de usuário</a>
                <a href="#" class="menu-item"><i class="fa fa-cog"></i> Configurações</a>
                <a href="#" class="menu-item"><i class="fa fa-shield-alt"></i> Security data</a>
                <a href="#" class="menu-item logout"><i class="fa fa-sign-out-alt"></i> Sair</a> -->
            </nav>
        </aside>

        <section class="feed">
            <h2 class="search-title">Resultados da Busca</h2>
            <div id="searchResults">Carregando...</div>
        </section>
    </main>

    <script>
        // Pega o termo da URL (ex: search.php?q=Isaque)
        const urlParams = new URLSearchParams(window.location.search);
        const searchTerm = urlParams.get('q');
        const resultsContainer = document.getElementById('searchResults');

        // Reutiliza a barra de busca nesta página também
        document.querySelector('.search-input').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') window.location.href = `search.php?q=${encodeURIComponent(this.value)}`;
        });

        // Função para buscar e exibir
        async function performSearch() {
            if (!searchTerm) {
                resultsContainer.innerHTML = '<p>Digite algo para buscar.</p>';
                return;
            }

            const fd = new FormData();
            fd.append('term', searchTerm);

            try {
                const res = await fetch('api.php?action=search_users', { method: 'POST', body: fd });
                const j = await res.json();

                if (j.status === 'ok') {
                    if (j.users.length === 0) {
                        resultsContainer.innerHTML = '<p>Nenhum usuário encontrado.</p>';
                        return;
                    }

                        resultsContainer.innerHTML = j.users.map(u => {
                        // Lógica do botão (Adicionar ou Remover)
                        let btnHtml = '';
                        if (u.is_friend > 0) {
                            btnHtml = `<button class="btn-friend remove" onclick="toggleFriend(this, ${u.id}, false)"><i class="fa fa-user-minus"></i> Remover</button>`;
                        } else {
                            btnHtml = `<button class="btn-friend" onclick="toggleFriend(this, ${u.id}, true)"><i class="fa fa-user-plus"></i> Adicionar</button>`;
                        }

                        return `
                            <div class="user-result-card">
                                <a href="profile.php?id=${u.id}" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px; flex-grow: 1;">
                                    <div class="user-result-info">
                                        <img src="${u.avatar}" alt="${u.nome}">
                                        <div>
                                            <strong>${u.nome}</strong><br>
                                            <small>${u.localizacao || 'Brasil'}</small>
                                        </div>
                                    </div>
                                </a>
                                <div class="user-result-actions">
                                    ${btnHtml}
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error(error);
                resultsContainer.innerHTML = '<p>Erro ao buscar.</p>';
            }
        }

        // Função de adicionar/remover (copiada do feed para funcionar aqui também)
        async function toggleFriend(btn, amigoId, isAdding) {
            btn.disabled = true;
            const action = isAdding ? 'add_friend' : 'remove_friend';
            const fd = new FormData();
            fd.append('amigo_id', amigoId);

            try {
                const res = await fetch(`api.php?action=${action}`, { method: 'POST', body: fd });
                const j = await res.json();
                if (j.status === 'ok') {
                    // Atualiza o botão visualmente sem recarregar
                    if (isAdding) {
                        btn.className = 'btn-friend remove';
                        btn.innerHTML = '<i class="fa fa-user-minus"></i> Remover';
                        btn.onclick = () => toggleFriend(btn, amigoId, false);
                    } else {
                        btn.className = 'btn-friend';
                        btn.innerHTML = '<i class="fa fa-user-plus"></i> Adicionar';
                        btn.onclick = () => toggleFriend(btn, amigoId, true);
                    }
                }
                btn.disabled = false;
            } catch (error) {
                console.error(error);
                btn.disabled = false;
            }
        }

        performSearch();
    </script>
</body>
</html>