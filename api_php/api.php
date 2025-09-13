<?php
require 'config.php';
header('Content-Type: application/json; charset=utf-8');
session_start();

$action = $_REQUEST['action'] ?? '';

if ($action === 'register') {
    // expects multipart/form-data: name,email,password,nascimento,avatar
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $nascimento = $_POST['nascimento'] ?? null;

    if (!$name || !$email || !$password) {
        echo json_encode(['status'=>'error','msg'=>'Dados incompletos']);
        exit;
    }

    // idade >= 16
    if ($nascimento) {
        $birth = new DateTime($nascimento);
        $today = new DateTime();
        $age = $today->diff($birth)->y;
        if ($age < 16) {
            echo json_encode(['status'=>'error','msg'=>'Idade mínima 16 anos']);
            exit;
        }
    }

    // checar email
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['status'=>'error','msg'=>'Email já cadastrado']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $avatarPath = null;
    if (!empty($_FILES['avatar']['tmp_name'])) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = __DIR__ . '/uploads/' . $filename;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
            $avatarPath = 'uploads/' . $filename;
        }
    }

    $stmt = $pdo->prepare('INSERT INTO usuarios (nome,email,senha,nascimento,avatar,created_at) VALUES (?,?,?,?,?,NOW())');
    $stmt->execute([$name,$email,$hash,$nascimento,$avatarPath]);

    echo json_encode(['status'=>'ok','msg'=>'Cadastro realizado']);
    exit;
}

if ($action === 'login') {
    // expects JSON or form POST: email,password
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['status'=>'error','msg'=>'Informe email e senha']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id,nome,senha,avatar FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['senha'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_avatar'] = $user['avatar'];
        echo json_encode(['status'=>'ok','msg'=>'Login bem-sucedido']);
        exit;
    } else {
        echo json_encode(['status'=>'error','msg'=>'Usuário ou senha inválidos']);
        exit;
    }
}

if ($action === 'create_post') {
    // create post for authenticated user, expects multipart/form-data: text,image
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['status'=>'error','msg'=>'Não autenticado']);
        exit;
    }

    $text = trim($_POST['text'] ?? '');
    $imagePath = null;
    if (!empty($_FILES['image']['tmp_name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'post_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = __DIR__ . '/uploads/' . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $imagePath = 'uploads/' . $filename;
        }
    }

    $stmt = $pdo->prepare('INSERT INTO posts (user_id,content,image,created_at) VALUES (?,?,?,NOW())');
    $stmt->execute([$_SESSION['user_id'],$text,$imagePath]);

    echo json_encode(['status'=>'ok','msg'=>'Post criado']);
    exit;
}

if ($action === 'list_posts') {
    $stmt = $pdo->query('SELECT p.id,p.user_id,p.content,p.image,p.created_at,u.nome,u.avatar FROM posts p JOIN usuarios u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 100');
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status'=>'ok','posts'=>$posts]);
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['status'=>'ok','msg'=>'Logout realizado']);
    exit;
}

echo json_encode(['status'=>'error','msg'=>'Ação inválida']);
?>