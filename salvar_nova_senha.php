<?php
// salvar_nova_senha.php
session_start();
require_once __DIR__ . '/src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$token = $_POST['token'] ?? '';
$nova_senha = $_POST['nova_senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';

$pdo = Database::getInstance();

// Revalida o token
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > datetime('now')");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $_SESSION['erro'] = "Link inválido ou expirado.";
    header("Location: index.php");
    exit;
}

// Validação de senha forte (Mínimo 8 carac, 1 letra, 1 número, 1 especial)
$regex = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/';

if ($nova_senha !== $confirmar_senha) {
    $_SESSION['erro'] = "As senhas não coincidem.";
    header("Location: redefinir_senha.php?token=" . $token);
    exit;
} elseif (!preg_match($regex, $nova_senha)) {
    $_SESSION['erro'] = "A senha deve ter no mínimo 8 caracteres, incluindo letra, número e caractere especial.";
    header("Location: redefinir_senha.php?token=" . $token);
    exit;
}

// Atualiza a senha
$hash = password_hash($nova_senha, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, must_change_password = 0 WHERE email = ?");
$stmt->execute([$hash, $reset['email']]);

// Deleta o token usado
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
$stmt->execute([$reset['email']]);

$_SESSION['sucesso'] = "Senha redefinida com sucesso! Faça login com sua nova senha.";
header("Location: index.php");
exit;
?>