<?php
// auth.php
session_start();
require_once __DIR__ . '/src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$senha = $_POST['senha'] ?? '';

if (!$email) {
    $_SESSION['erro'] = "Por favor, insira um e-mail válido.";
    header("Location: index.php");
    exit;
}

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Verifica se o usuário existe e se a senha confere
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        
        // NOVA REGRA: Verifica se o usuário está ativo
        if ($usuario['ativo'] == 0) {
            $_SESSION['erro'] = "Sua conta está desativada. Contate o administrador.";
            header("Location: index.php");
            exit;
        }

        // Regenera o ID da sessão para evitar Session Fixation
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_role'] = $usuario['role'];

        // Se for o primeiro acesso (senha provisória), força a troca
        if ($usuario['must_change_password'] == 1) {
            header("Location: trocar_senha.php"); 
            exit;
        }

        // Redireciona para o sistema
        header("Location: dashboard.php"); 
        exit;

    } else {
        $_SESSION['erro'] = "E-mail ou senha incorretos.";
        header("Location: index.php");
        exit;
    }
} catch (Exception $e) {
    // Para produção, não exiba o erro real do banco na tela
    $_SESSION['erro'] = "Erro interno no sistema. Tente novamente.";
    header("Location: index.php");
    exit;
}
?>