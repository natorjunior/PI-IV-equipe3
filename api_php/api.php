<?php
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
function send_json($status, $msg, $extra = []) {
    echo json_encode(array_merge(['status' => $status, 'msg' => $msg], $extra));
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
// 🔑 LOGIN
// ============================================================
if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    if (!$email || !$password) send_json('error', 'Informe email e senha.');

    $stmt = $pdo->prepare('SELECT id, nome, senha, avatar FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['senha'])) {
        session_regenerate_id(true); // 🔐 previne session fixation
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_avatar'] = $user['avatar'];
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

/* Posteriormente tem que ser ajustado a função de listar
posts, pois do jeito que está ele não identifica quem fez a publicação*/
// ============================================================
// 📰 LISTAR POSTS (exibe apenas nome do usuário + conteúdo + imagem)
// ============================================================
if ($action === 'list_posts') {
    $stmt = $pdo->query('
	SELECT p.id, p.user_id, p.content, p.image,u.avatar, u.nome AS user_name
        FROM posts p
        JOIN usuarios u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 100
    ');
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    #foreach ($posts as &$p) {
    #    if (empty($p['image'])) $p['image'] = null;
    #}
    unset($p);

    echo json_encode(['status' => 'ok', 'posts' => $posts]);
    exit;
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
// 🚪 LOGOUT
// ============================================================
if ($action === 'logout') {
    session_destroy();
    send_json('ok', 'Logout realizado.');
}

// ============================================================
// 🚫 AÇÃO INVÁLIDA
// ============================================================
send_json('error', 'Ação inválida.');
