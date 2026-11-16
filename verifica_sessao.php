<?php
// verifica_sessao.php

// Configura o cookie para expirar quando o navegador fechar (lifetime = 0)
// E define um tempo máximo de inatividade no servidor (ex: 30 minutos)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, // Expira ao fechar o navegador
        'path' => '/',
        'domain' => '',
        'secure' => false, // Mude para true se estiver usando HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutos
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Verifica o tempo de inatividade (Timeout)
$timeout = 1800; // 30 minutos em segundos
if (isset($_SESSION['ultima_atividade'])) {
    $tempo_inativo = time() - $_SESSION['ultima_atividade'];
    if ($tempo_inativo > $timeout) {
        // Sessão expirada por inatividade
        session_unset();
        session_destroy();
        header("Location: index.php?timeout=1");
        exit;
    }
}

// Atualiza o timestamp da última atividade
$_SESSION['ultima_atividade'] = time();
?>