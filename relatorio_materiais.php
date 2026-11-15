<?php
// relatorio_materiais.php
session_start();
require_once __DIR__ . '/src/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$pdo = Database::getInstance();
$materiais = [];

if (isset($_GET['todos']) && $_GET['todos'] == 1) {
    // Relatório de todos os materiais
    $stmt = $pdo->query("SELECT * FROM materiais ORDER BY descricao ASC");
    $materiais = $stmt->fetchAll();
    $titulo = "Relação de Todos os Materiais";
} elseif (isset($_GET['ids']) && !empty($_GET['ids'])) {
    // Relatório de materiais selecionados
    $ids = explode(',', $_GET['ids']);
    $ids = array_filter($ids, 'is_numeric');
    
    if (count($ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM materiais WHERE id IN ($placeholders) ORDER BY descricao ASC");
        $stmt->execute($ids);
        $materiais = $stmt->fetchAll();
    }
    $titulo = "Relação de Materiais Selecionados";
} else {
    die("Nenhum material selecionado para impressão.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?> - Casa Forte</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #003B73; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #003B73; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0 0 0; font-size: 14px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; color: #333; text-transform: uppercase; font-size: 11px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; }
        
        /* REGRAS PARA IMPRESSÃO */
        @media print {
            /* 1. Remove as margens padrão do navegador (onde ele escreve a URL e Data) */
            @page {
                margin: 0;
            }
            
            /* 2. Devolve a margem para o conteúdo não colar na borda do papel */
            body {
                margin: 1.5cm;
                padding: 0;
            }

            .no-print { display: none; }
            th { background-color: #e0e0e0 !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background-color: #003B73; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px;">
            🖨️ Imprimir Relatório
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background-color: #ccc; color: #333; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px; margin-left: 10px;">
            Fechar
        </button>
    </div>

    <div class="header">
        <h1>Casa Forte - Representações Ltda</h1>
        <p><?= $titulo ?></p>
        <p>Gerado em: <?= date('d/m/Y H:i') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descrição do Material</th>
                <th class="text-center">Medida</th>
                <th class="text-right">R$ Unitário</th>
                <th class="text-right">% IPI</th>
                <th class="text-right">% ICMS</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($materiais as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['descricao']) ?></td>
                <td class="text-center"><?= htmlspecialchars($m['medida']) ?></td>
                <td class="text-right">R$ <?= number_format($m['preco_unitario'], 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format($m['ipi'], 2, ',', '.') ?>%</td>
                <td class="text-right"><?= number_format($m['icms'], 2, ',', '.') ?>%</td>
                <td class="text-center"><?= $m['ativo'] ? 'Ativo' : 'Inativo' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($materiais)): ?>
            <tr>
                <td colspan="6" class="text-center">Nenhum material encontrado.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        &copy; <?= date('Y') ?> AST7net.com.br. Todos os direitos reservados.
    </div>

</body>
</html>