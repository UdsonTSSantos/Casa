<?php
// salvar_pedido.php
session_start();
require_once __DIR__ . '/src/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pedidos.php");
    exit;
}

$pdo = Database::getInstance();

try {
    $pdo->beginTransaction();

    $pedido_id = !empty($_POST['pedido_id']) ? (int)$_POST['pedido_id'] : 0;
    $numero_pedido = (int)$_POST['numero_pedido'];
    $vendedor_id = (int)$_POST['vendedor_id'];
    $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
    $fornecedor_id = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;
    $cif_id = !empty($_POST['cif_id']) ? (int)$_POST['cif_id'] : null;

    $cliente_att = trim($_POST['cliente_att'] ?? '');
    $fornecedor_att = trim($_POST['fornecedor_att'] ?? '');
    $endereco_cobranca = trim($_POST['endereco_cobranca'] ?? '');
    $endereco_entrega = trim($_POST['endereco_entrega'] ?? '');
    $faturamento = trim($_POST['faturamento'] ?? '');
    $condicao_faturado = trim($_POST['condicao_faturado'] ?? '');
    $condicao_avista = trim($_POST['condicao_avista'] ?? '');
    $observacao = trim($_POST['observacao'] ?? '');
    $fob_fornecedor_texto = trim($_POST['fob_fornecedor_texto'] ?? '');

    $is_fob_fornecedor = isset($_POST['is_fob_fornecedor']) ? 1 : 0;
    $cond_faturado = isset($_POST['cond_faturado']) ? 1 : 0;
    $cond_avista = isset($_POST['cond_avista']) ? 1 : 0;
    $cond_cif = isset($_POST['cond_cif']) ? 1 : 0;

    $item_material_id = $_POST['item_material_id'] ?? [];
    $item_material_nome = $_POST['item_material'] ?? []; // Pega o texto digitado
    $item_medida = $_POST['item_medida'] ?? [];
    $item_qtd = $_POST['item_qtd'] ?? [];
    $item_preco = $_POST['item_preco'] ?? [];
    $item_ipi = $_POST['item_ipi'] ?? [];
    $item_icms = $_POST['item_icms'] ?? [];

    $total_sem_imposto = 0;
    $total_com_imposto = 0;
    $total_ipi = 0;
    $total_icms = 0;
    $qtd_itens = 0;

    $itens_para_salvar = [];

    for ($i = 0; $i < count($item_material_id); $i++) {
        $mat_id = !empty($item_material_id[$i]) ? (int)$item_material_id[$i] : null;
        $mat_nome = trim($item_material_nome[$i] ?? '');

        // Pula linhas completamente vazias
        if (empty($mat_id) && empty($mat_nome) && empty($item_qtd[$i]) && empty($item_preco[$i])) {
            continue;
        }

        $qtd = (float)str_replace(['.', ','], ['', '.'], $item_qtd[$i]);
        $preco = (float)str_replace(['.', ','], ['', '.'], $item_preco[$i]);
        $ipi = (float)str_replace(['.', ','], ['', '.'], $item_ipi[$i]);
        $icms = (float)str_replace(['.', ','], ['', '.'], $item_icms[$i]);
        
        $subtotal_sem = $qtd * $preco;
        $valor_ipi = $subtotal_sem * ($ipi / 100);
        $valor_icms = $subtotal_sem * ($icms / 100);
        $subtotal_com = $subtotal_sem + $valor_ipi + $valor_icms;

        $qtd_itens += $qtd;
        $total_sem_imposto += $subtotal_sem;
        $total_com_imposto += $subtotal_com;
        $total_ipi += $valor_ipi;
        $total_icms += $valor_icms;

        $itens_para_salvar[] = [
            'material_id' => $mat_id,
            'descricao_manual' => $mat_nome, // Salva o nome manual
            'quantidade' => $qtd,
            'preco_unitario' => $preco,
            'ipi' => $ipi,
            'icms' => $icms,
            'subtotal_sem_imposto' => $subtotal_sem,
            'subtotal_com_imposto' => $subtotal_com
        ];
    }

    if ($pedido_id === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO pedidos (
                numero_pedido, vendedor_id, cliente_id, cliente_att, fornecedor_id, cif_id, 
                endereco_cobranca, endereco_entrega, faturamento, condicao_faturado, condicao_avista, 
                is_fob_fornecedor, fob_fornecedor_texto, observacao, total_sem_imposto, total_com_imposto, total_ipi, total_icms, qtd_itens, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'orcamento')
        ");
        $stmt->execute([
            $numero_pedido, $vendedor_id, $cliente_id, $cliente_att, $fornecedor_id, $cif_id,
            $endereco_cobranca, $endereco_entrega, $faturamento, $condicao_faturado, $condicao_avista,
            $is_fob_fornecedor, $fob_fornecedor_texto, $observacao, $total_sem_imposto, $total_com_imposto, $total_ipi, $total_icms, $qtd_itens
        ]);
        $pedido_id = $pdo->lastInsertId();
    } else {
        $stmt = $pdo->prepare("
            UPDATE pedidos SET 
                vendedor_id=?, cliente_id=?, cliente_att=?, fornecedor_id=?, cif_id=?, 
                endereco_cobranca=?, endereco_entrega=?, faturamento=?, condicao_faturado=?, condicao_avista=?, 
                is_fob_fornecedor=?, fob_fornecedor_texto=?, observacao=?, total_sem_imposto=?, total_com_imposto=?, total_ipi=?, total_icms=?, qtd_itens=?
            WHERE id = ?
        ");
        $stmt->execute([
            $vendedor_id, $cliente_id, $cliente_att, $fornecedor_id, $cif_id,
            $endereco_cobranca, $endereco_entrega, $faturamento, $condicao_faturado, $condicao_avista,
            $is_fob_fornecedor, $fob_fornecedor_texto, $observacao, $total_sem_imposto, $total_com_imposto, $total_ipi, $total_icms, $qtd_itens,
            $pedido_id
        ]);

        $pdo->prepare("DELETE FROM pedidos_itens WHERE pedido_id = ?")->execute([$pedido_id]);
    }

    $stmt_item = $pdo->prepare("
        INSERT INTO pedidos_itens (
            pedido_id, material_id, descricao_manual, quantidade, preco_unitario, ipi, icms, subtotal_sem_imposto, subtotal_com_imposto
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($itens_para_salvar as $item) {
        $stmt_item->execute([
            $pedido_id,
            $item['material_id'],
            $item['descricao_manual'],
            $item['quantidade'],
            $item['preco_unitario'],
            $item['ipi'],
            $item['icms'],
            $item['subtotal_sem_imposto'],
            $item['subtotal_com_imposto']
        ]);
    }

    $pdo->commit();

    $_SESSION['sucesso'] = "Pedido nº " . str_pad($numero_pedido, 5, '0', STR_PAD_LEFT) . " salvo com sucesso!";
    header("Location: pedidos.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['erro'] = "Erro ao salvar o pedido: " . $e->getMessage();
    header("Location: novo_pedido.php" . ($pedido_id > 0 ? "?id=$pedido_id" : ""));
    exit;
}
?>