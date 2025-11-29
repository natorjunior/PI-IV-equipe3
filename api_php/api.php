<?php
ob_start();
require 'config.php';
header('Content-Type: application/json; charset=utf-8');
session_start();

$action = $_REQUEST['action'] ?? '';

// ============================================================
// 🔒 Proteção contra Session Hijacking
// ============================================================
if (!empty($_SESSION['user_id'])) {
    if (!isset($_SESSION['ip'])) {
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'];
    } elseif ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['ua'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        echo json_encode(['status' => 'error', 'msg' => 'Sessão inválida. Faça login novamente.']);
        exit;
    }
}

// ============================================================
// 🧩 Funções auxiliares
// ============================================================
function send_json($status, $msg, $data = []) {
    // Limpa qualquer texto (warnings, errors, espaços em branco) que o PHP tenha gerado antes
    ob_clean(); 
    
    echo json_encode(array_merge(['status' => $status, 'msg' => $msg], $data));
    exit;
}

function validate_and_save_image($fileField, $prefix) {
    if (empty($_FILES[$fileField]['tmp_name'])) return null;

    $file = $_FILES[$fileField];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Verifica extensão permitida
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowedExt)) {
        send_json('error', 'Tipo de arquivo não permitido.');
    }

    // Verifica tipo MIME (proteção real)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (strpos($mime, 'image/') !== 0) {
        send_json('error', 'Arquivo inválido. Somente imagens são aceitas.');
    }

    $filename = "{$prefix}_" . time() . "_" . bin2hex(random_bytes(4)) . ".$ext";
    $target = __DIR__ . "/imagens/" . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        send_json('error', 'Erro ao salvar o arquivo.');
    }

    return "imagens/" . $filename;
}

// ============================================================
// 🧍 REGISTRO
// ============================================================
if ($action === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $nascimento = $_POST['nascimento'] ?? null;

    if (!$name || !$email || !$password) send_json('error', 'Dados incompletos.');

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $avatarPath = validate_and_save_image('avatar', 'avatar');

    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, nascimento, avatar, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$name, $email, $hash, $nascimento, $avatarPath]);

    send_json('ok', 'Cadastro realizado com sucesso.');
}

// ============================================================
// 🔑 LOGIN (CORRIGIDO PARA CARREGAR BIO E CAPA)
// ============================================================
if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    if (!$email || !$password) send_json('error', 'Informe email e senha.');

    // Seleciona TUDO (*) da tabela usuarios para garantir que pegamos bio, capa, etc.
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['senha'])) {
        session_regenerate_id(true); // Segurança
        
        // Dados Básicos
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_avatar'] = $user['avatar'];

        // --- A CORREÇÃO ESTÁ AQUI ---
        // Precisamos colocar os novos campos na sessão também!
        
        // Carrega a BIO (se não existir no banco, deixa vazio)
        $_SESSION['user_bio'] = $user['bio'] ?? '';
        
        // Carrega a LOCALIZAÇÃO
        $_SESSION['user_localizacao'] = $user['localizacao'] ?? '';
        
        // Carrega a CAPA (se existir, não esqueça de manter o padrão do caminho)
        // Nota: Se no banco já está salvo como "imagens/capa.jpg", aqui está ok.
        $_SESSION['user_capa'] = !empty($user['capa']) ? $user['capa'] : null;
        // -----------------------------
        $_SESSION['user_sobre'] = $user['sobre'] ?? '';

        send_json('ok', 'Login bem-sucedido.');
    }

    send_json('error', 'Usuário ou senha inválidos.');
}

// ============================================================
// 📝 CRIAR POST
// ============================================================
// ---------- create_post (versão segura e consistente) ----------
if ($action === 'create_post') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Sessão expirada. Faça login novamente.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $content = trim($_POST['text'] ?? '');
    $image_path = null;

    // --- Upload: só se houver arquivo e for upload válido ---
    if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
        $file = $_FILES['image'];

        // Verifica que é upload válido
        if (!is_uploaded_file($file['tmp_name'])) {
            echo json_encode(['status'=>'error','msg'=>'Upload inválido.']);
            exit;
        }

        // Limite de tamanho (5 MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            echo json_encode(['status'=>'error','msg'=>'Arquivo muito grande. Máx 5MB.']);
            exit;
        }

        // Extensão permitida
        $allowedExt = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            echo json_encode(['status'=>'error','msg'=>'Extensão não permitida.']);
            exit;
        }

        // MIME verificado no servidor (finfo)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMime = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($mime, $allowedMime)) {
            echo json_encode(['status'=>'error','msg'=>'Tipo MIME não permitido.']);
            exit;
        }

        // Diretório unificado: /imagens/posts/
        $uploadDir = __DIR__ . '/imagens/posts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = 'post_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            echo json_encode(['status'=>'error','msg'=>'Falha ao salvar imagem.']);
            exit;
        }
        // permissões seguras
        @chmod($target, 0644);

        // Caminho a salvar no banco (consistente com avatar paths)
        $image_path = 'imagens/posts/' . $filename;
    }

    // --- Proteção adicional: não aceitar que imagem do post seja igual ao avatar em sessão ---
    if (!empty($image_path) && !empty($_SESSION['user_avatar']) && $image_path === $_SESSION['user_avatar']) {
        $image_path = null;
    }

    // Insere no banco (NULL se sem imagem)
    $stmt = $pdo->prepare('INSERT INTO posts (user_id, content, image, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$user_id, $content, $image_path]);

    // Retorna o image_path para debug no frontend (útil para checar)
    echo json_encode(['status' => 'ok', 'msg' => 'Post criado com sucesso.', 'image' => $image_path]);
    exit;
}

// ============================================================
// LISTAR POSTS (Global ou Por Usuário)
// ============================================================
if ($action === 'list_posts') {
    $my_id = $_SESSION['user_id'] ?? 0;
    
    // Verifica se foi pedido o feed de um usuário específico
    $filter_user_id = $_POST['user_id'] ?? null; 

    // Query simplificada primeiro para testar
    if ($filter_user_id) {
        $sql = "SELECT p.id, p.user_id, p.content, p.image, p.created_at, 
                       u.nome, u.avatar,
                       COALESCE((SELECT COUNT(*) FROM amizades WHERE usuario_id = ? AND amigo_id = p.user_id), 0) as is_friend,
                       COALESCE((SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0) as like_count,
                       COALESCE((SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?), 0) as user_liked
                FROM posts p 
                JOIN usuarios u ON p.user_id = u.id 
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC LIMIT 100";
        $params = [$my_id, $my_id, $filter_user_id];
    } else {
        $sql = "SELECT p.id, p.user_id, p.content, p.image, p.created_at, 
                       u.nome, u.avatar,
                       COALESCE((SELECT COUNT(*) FROM amizades WHERE usuario_id = ? AND amigo_id = p.user_id), 0) as is_friend,
                       COALESCE((SELECT COUNT(*) FROM likes WHERE post_id = p.id), 0) as like_count,
                       COALESCE((SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?), 0) as user_liked
                FROM posts p 
                JOIN usuarios u ON p.user_id = u.id 
                ORDER BY p.created_at DESC LIMIT 100";
        $params = [$my_id, $my_id];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($posts as &$p) {
            if ($p['image']) $p['image'] = '../' . $p['image'];
            if ($p['avatar']) $p['avatar'] = '../' . $p['avatar'];
            else $p['avatar'] = '../imagens/default_avatar.png';
        }

        send_json('ok', '', ['posts' => $posts]);
    } catch (Exception $e) {
        send_json('error', 'Erro SQL: ' . $e->getMessage());
    }
}

// ============================================================
// 🗑️ DELETAR POST
// ============================================================
if ($action === 'delete_post') {
    if (empty($_SESSION['user_id'])) send_json('error', 'Não autenticado.');

    $post_id = (int)($_POST['post_id'] ?? 0);
    if (!$post_id) send_json('error', 'ID do post inválido.');

    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $stmt->execute([$post_id, $_SESSION['user_id']]);

    if ($stmt->rowCount()) {
        send_json('ok', 'Post excluído com sucesso.');
    } else {
        send_json('error', 'Falha ao excluir o post ou permissão negada.');
    }
}
// ============================================================
// 🔍 BUSCAR USUÁRIOS
// ============================================================
if ($action === 'search_users') {
    $term = $_POST['term'] ?? '';
    $my_id = $_SESSION['user_id'] ?? 0;

    if (strlen($term) < 1) send_json('error', 'Digite algo para buscar.');

    // Busca usuários onde o nome parece com o termo (LIKE)
    // Também verifica se já é amigo (is_friend)
    $sql = "
        SELECT 
            id, nome, avatar, bio, localizacao,
            (SELECT COUNT(*) FROM amizades WHERE usuario_id = ? AND amigo_id = usuarios.id) as is_friend
        FROM usuarios 
        WHERE nome LIKE ? AND id != ?
        LIMIT 20
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$my_id, "%$term%", $my_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Arruma os caminhos das imagens
    foreach ($users as &$u) {
        $u['avatar'] = $u['avatar'] ? '../' . $u['avatar'] : '../imagens/default_avatar.png';
    }

    send_json('ok', '', ['users' => $users]);
}
// ============================================================
// 🖼️ ATUALIZAR AVATAR
// ============================================================
if ($action === 'update_avatar') {
    if (empty($_SESSION['user_id'])) send_json('error', 'Não autenticado.');

    $avatarPath = validate_and_save_image('avatar', 'avatar_' . $_SESSION['user_id']);
    if (!$avatarPath) send_json('error', 'Nenhum arquivo enviado.');

    $stmt = $pdo->prepare('UPDATE usuarios SET avatar = ? WHERE id = ?');
    $stmt->execute([$avatarPath, $_SESSION['user_id']]);
    $_SESSION['user_avatar'] = $avatarPath;

    send_json('ok', 'Foto de perfil atualizada com sucesso!', ['avatar' => $avatarPath]);
}


// ============================================================
//  ADICIONAR AMIGO
// ============================================================
if ($action === 'add_friend') {
    if (empty($_SESSION['user_id'])) send_json('error', 'Não autenticado.');
    $amigo_id = (int)($_POST['amigo_id'] ?? 0);
    $usuario_id = $_SESSION['user_id'];

    if ($amigo_id === $usuario_id) send_json('error', 'Ação inválida.');

    // Verifica se já existe para não duplicar erro
    $stmt = $pdo->prepare('SELECT id FROM amizades WHERE usuario_id = ? AND amigo_id = ?');
    $stmt->execute([$usuario_id, $amigo_id]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare('INSERT INTO amizades (usuario_id, amigo_id, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$usuario_id, $amigo_id]);
    }
    send_json('ok', 'Amigo adicionado!');
}

// ============================================================
//  REMOVER AMIGO
// ============================================================
if ($action === 'remove_friend') {
    if (empty($_SESSION['user_id'])) send_json('error', 'Não autenticado.');
    $amigo_id = (int)($_POST['amigo_id'] ?? 0);
    $usuario_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare('DELETE FROM amizades WHERE usuario_id = ? AND amigo_id = ?');
    $stmt->execute([$usuario_id, $amigo_id]);

    send_json('ok', 'Amigo removido.');
}

// ============================================================
// 👥 LISTAR AMIGOS (CONEXÕES)
// ============================================================
if ($action === 'list_friends') {
    // Se um ID for passado, lista os amigos desse ID. Se não, lista os do usuário logado.
    $target_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $_SESSION['user_id'];

    // Busca os dados dos usuários que o target_id adicionou
    $stmt = $pdo->prepare('
        SELECT u.id, u.nome, u.avatar, u.bio 
        FROM amizades a
        JOIN usuarios u ON a.amigo_id = u.id
        WHERE a.usuario_id = ?
        ORDER BY a.created_at DESC
    ');
    $stmt->execute([$target_id]);
    $amigos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajusta caminhos das imagens
    foreach ($amigos as &$amigo) {
        $amigo['avatar'] = $amigo['avatar'] ? '../' . $amigo['avatar'] : '../imagens/default_avatar.png';
    }

    send_json('ok', '', ['amigos' => $amigos]);
}

// ============================================================
// ❤️ CURTIR / DESCURTIR POST (TOGGLE)
// ============================================================
if ($action === 'toggle_like') {
    if (empty($_SESSION['user_id'])) send_json('error', 'Não autenticado.');

    $post_id = (int)($_POST['post_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if (!$post_id) send_json('error', 'Post inválido.');

    // Verifica se já curtiu
    $stmt = $pdo->prepare('SELECT id FROM likes WHERE user_id = ? AND post_id = ?');
    $stmt->execute([$user_id, $post_id]);
    $existingLike = $stmt->fetch();

    if ($existingLike) {
        // Se já curtiu, REMOVE (Descurtir)
        $stmt = $pdo->prepare('DELETE FROM likes WHERE id = ?');
        $stmt->execute([$existingLike['id']]);
        $liked = false;
    } else {
        // Se não curtiu, ADICIONA (Curtir)
        $stmt = $pdo->prepare('INSERT INTO likes (user_id, post_id) VALUES (?, ?)');
        $stmt->execute([$user_id, $post_id]);
        $liked = true;
    }

    // Conta o novo total de likes
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM likes WHERE post_id = ?');
    $stmt->execute([$post_id]);
    $count = $stmt->fetch()['total'];

    send_json('ok', 'Sucesso', ['liked' => $liked, 'count' => $count]);
}

// ============================================================
// 🚪 LOGOUT
// ============================================================
if ($action === 'logout') {
    session_destroy();
    send_json('ok', 'Logout realizado.');
}

// ==========================================================
// AÇÃO: 'update_profile' (Atualiza Capa, Bio e Localização)
// ==========================================================
if ($action === 'update_profile') {
    if (empty($_SESSION['user_id'])) send_json('error', 'Não autenticado');

    $user_id = $_SESSION['user_id'];
    $bio = $_POST['bio'] ?? '';
    $localizacao = $_POST['localizacao'] ?? '';
    $sobre = $_POST['sobre'] ?? ''; // O novo texto longo
    
    // Prepara a query básica
    $sql = "UPDATE usuarios SET bio = ?, localizacao = ?, sobre = ?";
    $params = [$bio, $localizacao, $sobre];

    // --- LÓGICA DA CAPA ---
    if (!empty($_FILES['capa']['name'])) {
        
        if ($_FILES['capa']['error'] !== UPLOAD_ERR_OK) {
            send_json('error', "Erro no upload: Código " . $_FILES['capa']['error']);
        }

        $ext = pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            send_json('error', 'Formato inválido.');
        }

        $filename = 'capa_' . $user_id . '_' . time() . '.' . $ext;
        
        // ==========================================================
        // A CORREÇÃO ESTÁ AQUI: Removemos o '../'
        // ==========================================================
        $target = __DIR__ . '/imagens/' . $filename; // Caminho correto na pasta raiz
        
        if (move_uploaded_file($_FILES['capa']['tmp_name'], $target)) {
            $capaPathDB = 'imagens/' . $filename;
            
            $sql .= ", capa = ?";
            $params[] = $capaPathDB;
            
            // Adiciona o prefixo para a sessão também
            $_SESSION['user_capa'] = '../' . $capaPathDB;
        } else {
            // Se ainda falhar aqui, é 100% permissão (chmod 777)
            send_json('error', 'Falha ao mover arquivo. Verifique chmod 777 na pasta imagens.');
        }
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $_SESSION['user_bio'] = $bio;
        $_SESSION['user_localizacao'] = $localizacao;
        $_SESSION['user_sobre'] = $sobre; // <--- Atualiza a sessão aqui

        send_json('ok', 'Perfil atualizado com sucesso!');
    } catch (Exception $e) {
        send_json('error', 'Erro SQL: ' . $e->getMessage());
    }
}

// ============================================================
// 🚫 AÇÃO INVÁLIDA
// ============================================================
send_json('error', 'Ação inválida.');
