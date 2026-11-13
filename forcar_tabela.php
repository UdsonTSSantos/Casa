<?php
// forcar_tabela.php
$dbPath = __DIR__ . '/storage/casaforte.db';

// Conecta direto no banco
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Tenta criar a tabela fornecedores na força bruta
$sql = "CREATE TABLE IF NOT EXISTS fornecedores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    razao_social TEXT NOT NULL,
    cep TEXT,
    endereco TEXT,
    numero TEXT,
    bairro TEXT,
    cidade TEXT,
    uf TEXT,
    cnpj TEXT,
    ie TEXT,
    telefone TEXT,
    fax TEXT,
    contato_nome TEXT,
    celular TEXT,
    email TEXT,
    whatsapp TEXT,
    email_danfe TEXT,
    observacao TEXT,
    ativo INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

try {
    $pdo->exec($sql);
    echo "<h2 style='color: green;'>Tabela 'fornecedores' criada com sucesso!</h2>";
    
    // Mostra todas as tabelas para conferência
    echo "<h3>Tabelas no banco:</h3>";
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
    echo "<ul>";
    foreach ($tables as $t) {
        echo "<li>" . $t['name'] . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erro ao criar tabela:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>