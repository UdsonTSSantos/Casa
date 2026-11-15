<?php
// atualizar_banco.php
$dbPath = __DIR__ . '/storage/casaforte.db';

if (!file_exists($dbPath)) {
    die("Banco de dados não encontrado.");
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Verifica se a coluna já existe
    $colunas = $pdo->query("PRAGMA table_info(pedidos)")->fetchAll(PDO::FETCH_ASSOC);
    $coluna_existe = false;
    
    foreach ($colunas as $col) {
        if ($col['name'] === 'fob_fornecedor_texto') {
            $coluna_existe = true;
            break;
        }
    }

    if (!$coluna_existe) {
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN fob_fornecedor_texto TEXT");
        echo "<h2 style='color: green;'>Sucesso! A coluna 'fob_fornecedor_texto' foi adicionada à tabela de pedidos.</h2>";
    } else {
        echo "<h2 style='color: blue;'>A coluna 'fob_fornecedor_texto' já existe no banco.</h2>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erro ao atualizar o banco: " . $e->getMessage() . "</h2>";
}
?>