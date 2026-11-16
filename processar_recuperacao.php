<?php
// processar_recuperacao.php
session_start();
require_once __DIR__ . '/src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
    $_SESSION['erro'] = "E-mail inválido.";
    header("Location: esqueci_senha.php");
    exit;
}

$pdo = Database::getInstance();

// Verifica se o e-mail existe
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND ativo = 1");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if ($usuario) {
    // Gera um token seguro
    $token = bin2hex(random_bytes(32));
    // Define expiração para daqui a 1 hora
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Salva o token no banco
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token, $expires_at]);

    // Monta o link de redefinição
    // Em produção na Locaweb, mude 'localhost/Casa' para o seu domínio real (https://...)
    $link = "http://localhost/Casa/redefinir_senha.php?token=" . $token;

    // --- ENVIO DE E-MAIL ---
    // O código abaixo usa a função nativa mail() do PHP que funciona bem no XAMPP.
    // Na Locaweb, isso provavelmente será bloqueado. O correto em produção é usar PHPMailer/SMTP.
    
    $assunto = "Recuperação de Senha - Casa Forte";
    $mensagem = "Você solicitou a recuperação de senha.\n\nClique no link abaixo para criar uma nova senha:\n$link\n\nSe não foi você, ignore este e-mail.";
    $headers = "From: no-reply@casaforte.com.br";

    // Tenta enviar (em localhost pode falhar ou cair no spam)
    @mail($email, $assunto, $mensagem, $headers);

    // Para fins de teste local, salvamos o link na sessão para mostrar na tela
    $_SESSION['link_teste'] = $link;
    
    $_SESSION['sucesso'] = "Se o e-mail estiver cadastrado, você receberá um link de recuperação em breve.";
} else {
    // Por segurança, não informamos se o e-mail existe ou não
    $_SESSION['sucesso'] = "Se o e-mail estiver cadastrado, você receberá um link de recuperação em breve.";
}

header("Location: esqueci_senha.php");
exit;
?>