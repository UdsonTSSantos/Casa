<?php
// debug_login.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/Database.php';

echo "<h2>Tentando conectar ao banco...</h2>";

try {
    $pdo = Database::getInstance();
    echo "<p style='color: green'>Conexão bem-sucedida!</p>";

    echo "<h3>Verificando usuários...</h3>";
    $stmt = $pdo->query("SELECT id, nome, email FROM usuarios");
    $usuarios = $stmt->fetchAll();

    if (count($usuarios) > 0) {
        echo "<p style='color: green'>O usuário admin existe:</p>";
        echo "<pre>"; print_r($usuarios); echo "</pre>";
    } else {
        echo "<p style='color: red'>Nenhum usuário encontrado na tabela. O script de criação falhou.</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red'>ERRO FATAL:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>