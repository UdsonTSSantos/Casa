<?php
// atualizar_materiais.php
$dbPath = __DIR__ . '/storage/casaforte.db';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // 1. Apaga a tabela antiga
    $pdo->exec("DROP TABLE IF EXISTS materiais");
    echo "Tabela antiga de materiais removida.<br>";

    // 2. Cria a nova tabela com a estrutura correta
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS materiais (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao TEXT NOT NULL,
            medida TEXT NOT NULL,
            preco_unitario REAL DEFAULT 0.00,
            ipi REAL DEFAULT 0.00,
            icms REAL DEFAULT 0.00,
            ativo INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<strong style='color: green;'>Nova tabela de materiais criada com sucesso!</strong><br>";
    echo "Agora você pode voltar para a tela de materiais e cadastrar.";

} catch (Exception $e) {
    echo "<strong style='color: red;'>Erro:</strong> " . $e->getMessage();
}
?>