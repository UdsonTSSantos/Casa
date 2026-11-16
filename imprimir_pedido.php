<?php
// imprimir_pedido.php
session_start();
require_once __DIR__ . '/src/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pedido_id === 0) {
    die("Pedido inválido.");
}

$pdo = Database::getInstance();

// --- BUSCA DE DADOS ---
// Empresa
$empresa = $pdo->query("SELECT * FROM empresa WHERE id = 1")->fetch();
if (!$empresa) $empresa = [];

// Pedido
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch();
if (!$pedido) die("Pedido não encontrado.");

// Cliente, Fornecedor, Vendedor, CIF
$cliente = null;
if ($pedido['cliente_id']) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$pedido['cliente_id']]);
    $cliente = $stmt->fetch();
}

$fornecedor = null;
if ($pedido['fornecedor_id']) {
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
    $stmt->execute([$pedido['fornecedor_id']]);
    $fornecedor = $stmt->fetch();
}

$stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt->execute([$pedido['vendedor_id']]);
$vendedor = $stmt->fetch();

$cif_nome = '';
if ($pedido['cif_id']) {
    $stmt = $pdo->prepare("SELECT razao_social FROM cifs WHERE id = ?");
    $stmt->execute([$pedido['cif_id']]);
    $cif = $stmt->fetch();
    if ($cif) $cif_nome = $cif['razao_social'];
}

// Itens
$stmt_itens = $pdo->prepare("
    SELECT pi.*, m.descricao, m.medida 
    FROM pedidos_itens pi 
    LEFT JOIN materiais m ON pi.material_id = m.id 
    WHERE pi.pedido_id = ?
");
$stmt_itens->execute([$pedido_id]);
$itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

// Função auxiliar para formatar moeda
function moeda($valor) {
    return number_format((float)$valor, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressão de Pedido - <?= str_pad($pedido['numero_pedido'], 5, '0', STR_PAD_LEFT) ?></title>
    <style>
        /* Reset básico */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { font-size: 11px; color: #000; background: #fff; line-height: 1.3; }
        
        /* Configurações da Folha A4 */
        .page { width: 21cm; min-height: 29.7cm; padding: 1cm; margin: 0 auto; background: white; }
        
        /* Estrutura Geral */
        .header-container { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
        
        /* Cabeçalho */
        .header-logo img { max-height: 80px; max-width: 150px; object-fit: contain; }
        .header-empresa { flex: 1; padding-left: 15px; }
        .header-empresa h1 { font-size: 16px; text-transform: uppercase; color: #003B73; margin-bottom: 2px; }
        .header-empresa p { font-size: 11px; margin-bottom: 2px; font-weight: bold; }
        .header-empresa .contatos { font-size: 10px; }
        .header-direita { text-align: right; width: 180px; border-left: 1px solid #ccc; padding-left: 15px; }
        .header-direita .orcamento-num { font-size: 20px; font-weight: bold; color: #003B73; margin-bottom: 2px; }
        .header-direita p { margin-bottom: 2px; font-size: 10px; }
        
        /* Bloco Fornecedor / Cliente */
        .bloco-duplo { display: flex; justify-content: space-between; border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 10px; }
        .bloco-metade { width: 48%; }
        .bloco-metade h3 { font-size: 11px; text-transform: uppercase; color: #555; margin-bottom: 4px; }
        .bloco-metade strong { font-size: 12px; text-transform: uppercase; display: block; margin-bottom: 3px; }
        .bloco-metade p { margin-bottom: 2px; }

        /* Tabela de Itens */
        .tabela-itens { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; }
        .tabela-itens th { background-color: #f0f0f0; text-align: right; padding: 4px; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .tabela-itens th:first-child, .tabela-itens th:nth-child(2), .tabela-itens th:nth-child(3) { text-align: center; }
        .tabela-itens th:nth-child(4) { text-align: left; }
        .tabela-itens th.col-valores { text-align: center; border-left: 1px solid #ccc; }
        
        .tabela-itens td { padding: 4px; text-align: right; border-bottom: 1px solid #eee; }
        .tabela-itens td:first-child, .tabela-itens td:nth-child(2), .tabela-itens td:nth-child(3) { text-align: center; }
        .tabela-itens td:nth-child(4) { text-align: left; font-weight: bold; }
        .tabela-itens td.col-valores { border-left: 1px solid #ccc; }
        
        /* Linha de Totais Parciais */
        .linha-totais-parciais { display: flex; justify-content: space-between; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 15px; font-size: 10px; }
        .totais-esq { display: flex; gap: 20px; }
        .totais-esq div { text-align: center; }
        .totais-esq span { display: block; font-size: 9px; color: #666; }
        .totais-dir { display: flex; align-items: center; gap: 20px; }
        .totais-dir .total-box { text-align: right; }
        .totais-dir .total-box .valor { font-size: 18px; font-weight: bold; color: #003B73; }
        
        /* Bloco Condições e Endereços */
        .bloco-condicoes { display: flex; justify-content: space-between; border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .cond-col { width: 33%; }
        .cond-col h4 { font-size: 10px; text-transform: uppercase; color: #555; margin-bottom: 5px; border-bottom: 1px solid #eee; }
        .cond-col p { margin-bottom: 3px; }
        .cond-col .checkbox { display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 5px; vertical-align: middle; text-align: center; line-height: 8px; font-size: 10px; }
        
        /* Bloco FOB / CIF */
        .bloco-fob { display: flex; justify-content: space-between; border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 10px; }
        .fob-col { width: 33%; }
        .fob-col h4 { font-size: 10px; text-transform: uppercase; color: #555; margin-bottom: 5px; border-bottom: 1px solid #eee; }
        
        /* Bloco Cadastro Cliente */
        .bloco-cliente { border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 10px; font-size: 10px; }
        .bloco-cliente h4 { font-size: 10px; text-transform: uppercase; color: #555; margin-bottom: 5px; }
        .cliente-header { display: flex; justify-content: space-between; font-weight: bold; font-size: 11px; margin-bottom: 5px; }
        .cliente-dados { display: flex; justify-content: space-between; }
        .cliente-dados div { width: 48%; }
        .cliente-dados p { margin-bottom: 2px; }
        
        /* Observações */
        .bloco-obs { border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 10px; min-height: 80px; }
        .bloco-obs h4 { font-size: 10px; text-transform: uppercase; color: #555; margin-bottom: 5px; }
        
        /* Rodapé */
        .footer { display: flex; justify-content: space-between; align-items: flex-end; font-size: 9px; color: #555; margin-top: 20px; }
        .footer-logo img { max-height: 30px; }
        .footer-info { text-align: right; }
        .footer-info p { margin-bottom: 2px; }

        /* REGRAS DE IMPRESSÃO */
        @media print {
            @page { margin: 0; size: A4 portrait; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page { width: 100%; margin: 0; padding: 0.5cm 1cm; }
            .no-print { display: none !important; }
        }
        
        .no-print-btn {
            position: fixed; top: 20px; right: 20px; background: #003B73; color: white;
            padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; z-index: 9999;
        }
        .no-print-btn:hover { background: #002244; }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print-btn no-print">🖨️ Imprimir Pedido</button>

    <div class="page">
        
        <!-- CABEÇALHO -->
        <div class="header-container">
            <div class="header-logo">
                <?php if(!empty($empresa['logo']) && file_exists(__DIR__ . '/' . $empresa['logo'])): ?>
                    <img src="<?= $empresa['logo'] ?>" alt="Logo">
                <?php else: ?>
                    <div style="width: 150px; height: 80px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 10px;">SEM LOGO</div>
                <?php endif; ?>
            </div>
            
            <div class="header-empresa">
                <h1><?= htmlspecialchars($empresa['razao_social'] ?? 'EMPRESA') ?></h1>
                <p>
                    <?= htmlspecialchars($empresa['endereco'] ?? '') ?>, 
                    <?= htmlspecialchars($empresa['numero'] ?? '') ?> - 
                    <?= htmlspecialchars($empresa['bairro'] ?? '') ?><br>
                    <?= htmlspecialchars($empresa['cidade'] ?? '') ?> - <?= htmlspecialchars($empresa['uf'] ?? '') ?> - Cep:<?= htmlspecialchars($empresa['cep'] ?? '') ?>
                </p>
                <div class="contatos">
                    <p>
                        <?php 
                            $tels = [];
                            if(!empty($empresa['telefone1'])) $tels[] = $empresa['telefone1'];
                            if(!empty($empresa['telefone2'])) $tels[] = $empresa['telefone2'];
                            if(!empty($empresa['telefone3'])) $tels[] = $empresa['telefone3'];
                            if(!empty($empresa['celular1'])) $tels[] = $empresa['celular1'];
                            if(!empty($empresa['celular2'])) $tels[] = $empresa['celular2'];
                            echo implode(' | ', $tels);
                        ?>
                    </p>
                    <p>✉ <?= htmlspecialchars($empresa['email'] ?? '') ?></p>
                </div>
            </div>

            <div class="header-direita">
                <p>ORÇAMENTO Nº:</p>
                <div class="orcamento-num"><?= str_pad($pedido['numero_pedido'], 8, '0', STR_PAD_LEFT) ?></div>
                <p><?= date('Y', strtotime($pedido['data_pedido'])) ?></p>
                <p style="margin-top: 10px;">VENDEDOR</p>
                <p style="text-transform: uppercase;"><?= htmlspecialchars($vendedor['nome'] ?? '') ?></p>
                <p style="margin-top: 5px;">DATA DO ORÇAMENTO</p>
                <p><?= date('d/m/Y', strtotime($pedido['data_pedido'])) ?></p>
            </div>
        </div>

        <!-- FORNECEDOR E CLIENTE -->
        <div class="bloco-duplo">
            <div class="bloco-metade">
                <h3>FORNECEDOR:</h3>
                <strong><?= htmlspecialchars($fornecedor['razao_social'] ?? '') ?></strong>
                <p><?= htmlspecialchars(($fornecedor['endereco'] ?? '') . ', ' . ($fornecedor['numero'] ?? '') . ' - ' . ($fornecedor['bairro'] ?? '')) ?></p>
                <p>CNPJ: <?= htmlspecialchars($fornecedor['cnpj'] ?? '') ?> - IE: <?= htmlspecialchars($fornecedor['ie'] ?? '') ?></p>
                <p>Contato: <?= htmlspecialchars($fornecedor['contato_nome'] ?? '') ?></p>
                <p>Telefone: <?= htmlspecialchars($fornecedor['telefone'] ?? '') ?></p>
                <p>Att: <?= htmlspecialchars($pedido['fornecedor_att'] ?? '') ?></p>
            </div>
            
            <div class="bloco-metade">
                <h3>CLIENTE:</h3>
                <strong><?= htmlspecialchars($cliente['razao_social'] ?? '') ?></strong>
                <p><?= htmlspecialchars(($cliente['endereco'] ?? '') . ', ' . ($cliente['numero'] ?? '') . ' - ' . ($cliente['bairro'] ?? '')) ?></p>
                <p><?= htmlspecialchars($cliente['cidade'] ?? '') ?> - <?= htmlspecialchars($cliente['uf'] ?? '') ?></p>
                <p>CNPJ: <?= htmlspecialchars($cliente['cnpj'] ?: $cliente['cpf'] ?? '') ?> - IE: <?= htmlspecialchars($cliente['insc_estadual'] ?? '') ?></p>
                <p>E-mail: <?= htmlspecialchars($cliente['email'] ?? '') ?></p>
                <p>Att: <?= htmlspecialchars($pedido['cliente_att'] ?? '') ?></p>
            </div>
        </div>

        <!-- TABELA DE ITENS -->
        <table class="tabela-itens">
            <thead>
                <tr>
                    <th style="width: 30px;">Item</th>
                    <th style="width: 50px;">QTD</th>
                    <th style="width: 40px;">Medida</th>
                    <th>Descrição do Material</th>
                    <th style="width: 60px;" class="col-valores">UNIT</th>
                    <th style="width: 40px;">IPI</th>
                    <th style="width: 40px;">ICMS</th>
                    <th style="width: 80px;" class="col-valores">c/ imposto</th>
                    <th style="width: 80px;">s/ imposto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($itens as $index => $item): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= number_format($item['quantidade'], 3, ',', '.') ?></td>
                    <td><?= htmlspecialchars($item['medida'] ?? 'KGS') ?></td>
                    <td style="text-transform: uppercase;"><?= htmlspecialchars($item['descricao_manual'] ?: $item['descricao']) ?></td>
                    <td class="col-valores"><?= moeda($item['preco_unitario']) ?></td>
                    <td><?= number_format($item['ipi'], 2, ',', '.') ?>%</td>
                    <td><?= number_format($item['icms'], 2, ',', '.') ?>%</td>
                    <td class="col-valores"><?= moeda($item['subtotal_com_imposto']) ?></td>
                    <td><?= moeda($item['subtotal_sem_imposto']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($itens)): ?>
                <tr><td colspan="9" style="text-align:center; padding: 20px;">Nenhum item neste pedido.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- TOTAIS PARCIAIS -->
        <div class="linha-totais-parciais">
            <div class="totais-esq">
                <div>
                    <span>Número de itens</span>
                    <?= str_pad($pedido['qtd_itens'] > 0 ? count($itens) : 0, 6, '0', STR_PAD_LEFT) ?>
                </div>
                <div>
                    <span>Sem imposto</span>
                    <?= moeda($pedido['total_sem_imposto']) ?>
                </div>
                <div>
                    <span>R$ imposto</span>
                    <?= moeda($pedido['total_ipi'] + $pedido['total_icms']) ?>
                </div>
                <div>
                    <span>Com imposto</span>
                    <?= moeda($pedido['total_com_imposto']) ?>
                </div>
                <div style="font-size: 8px; max-width: 150px; text-align: left; color: #666;">
                    * 
                </div>
            </div>
            <div class="totais-dir">
                <div class="total-box">
                    <span style="font-size:10px; font-weight:bold; display:block;">T O T A L</span>
                    <span class="valor">R$ <?= moeda($pedido['total_sem_imposto']) ?></span>
                    <span style="display:block; font-size:10px; margin-top:2px;">Sem imposto</span>
                </div>
            </div>
        </div>

        <!-- CONDIÇÕES E ENDEREÇOS -->
        <div class="bloco-condicoes">
            <div class="cond-col" style="width: 20%;">
                <h4>FATURAMENTO</h4>
                <p><?= htmlspecialchars($pedido['faturamento'] ?? '') ?></p>
            </div>
            <div class="cond-col" style="width: 35%;">
                <h4>CONDIÇÕES DE PAGAMENTO:</h4>
                <p>
                    <span class="checkbox"><?= !empty($pedido['condicao_avista']) ? 'X' : '' ?></span>
                    À vista: <?= htmlspecialchars($pedido['condicao_avista'] ?? '') ?>
                </p>
                <p style="margin-top: 5px;">
                    <span class="checkbox"><?= !empty($pedido['condicao_faturado']) ? 'X' : '' ?></span>
                    Faturado: <?= htmlspecialchars($pedido['condicao_faturado'] ?? '') ?>
                </p>
            </div>
            <div class="cond-col" style="width: 40%;">
                <h4>ENDEREÇOS:</h4>
                <p><strong>Cobrança:</strong> <?= htmlspecialchars($pedido['endereco_cobranca'] ?? '') ?></p>
                <p style="margin-top: 5px;"><strong>Entrega:</strong> <?= htmlspecialchars($pedido['endereco_entrega'] ?? '') ?></p>
            </div>
        </div>

        <!-- FOB E CIF -->
        <div class="bloco-fob">
            <div class="fob-col">
                <h4 style="border:none;">
                    <span class="checkbox"><?= $pedido['is_fob_fornecedor'] ? 'X' : '' ?></span>
                    FOB Fornecedor:
                </h4>
                <p><?= htmlspecialchars($pedido['fob_fornecedor_texto'] ?? '') ?></p>
            </div>
            <div class="fob-col">
                <h4 style="border:none;">
                    <span class="checkbox"><?= !empty($pedido['cif_id']) ? 'X' : '' ?></span>
                    CIF - Local:
                </h4>
            </div>
            <div class="fob-col">
                <h4 style="border:none;">C I F:</h4>
                <p><?= htmlspecialchars($cif_nome) ?></p>
            </div>
        </div>

        <!-- CADASTRO DO CLIENTE -->
        <div class="bloco-cliente">
            <h4>CADASTRO DO CLIENTE:</h4>
            <div class="cliente-header">
                <span><?= htmlspecialchars($cliente['razao_social'] ?? '') ?></span>
                <span>Telefones:</span>
            </div>
            <div class="cliente-dados">
                <div>
                    <p>Endereço: <?= htmlspecialchars($cliente['endereco'] ?? '') ?>, <?= htmlspecialchars($cliente['numero'] ?? '') ?></p>
                    <p>Bairro: <?= htmlspecialchars($cliente['bairro'] ?? '') ?> - CEP: <?= htmlspecialchars($cliente['cep'] ?? '') ?> - Cidade: <?= htmlspecialchars($cliente['cidade'] ?? '') ?></p>
                    <p>UF: <?= htmlspecialchars($cliente['uf'] ?? '') ?> - Contato: <?= htmlspecialchars($cliente['contato_nome'] ?? '') ?></p>
                    <p>CNPJ: <?= htmlspecialchars($cliente['cnpj'] ?: $cliente['cpf'] ?? '') ?> - Inscr. Estadual: <?= htmlspecialchars($cliente['insc_estadual'] ?? '') ?></p>
                    <p>E-mail: <?= htmlspecialchars($cliente['email'] ?? '') ?> - DANFE: <?= htmlspecialchars($cliente['email_danfe'] ?? '') ?></p>
                </div>
                <div style="text-align: right;">
                    <p>Celular: <?= htmlspecialchars($cliente['celular'] ?? '') ?></p>
                    <p>Telefone: <?= htmlspecialchars($cliente['telefone'] ?? '') ?></p>
                    <p>WhatsApp: <?= htmlspecialchars($cliente['whatsapp'] ?? '') ?></p>
                </div>
            </div>
        </div>

        <!-- OBSERVAÇÕES -->
        <div class="bloco-obs">
            <h4>OBSERVAÇÕES:</h4>
            <p style="text-transform: uppercase;"><?= nl2br(htmlspecialchars($pedido['observacao'] ?? '')) ?></p>
        </div>

        <!-- RODAPÉ -->
        <div class="footer">
            <div class="footer-logo">
                <?php if(!empty($empresa['logo']) && file_exists(__DIR__ . '/' . $empresa['logo'])): ?>
                    <img src="<?= $empresa['logo'] ?>" alt="Logo Rodapé" style="filter: grayscale(100%);">
                <?php endif; ?>
                <p style="margin-top:5px; font-size:8px;">Reduza | Reuse | Recicle | Recupere | Respeite</p>
            </div>
            <div class="footer-info">
                <p>Impresso em: <?= date('d/m/Y H:i:s') ?></p>
                <p>Valores expressos em Reais (R$)</p>
                <p style="font-weight: bold; color: #000;"><?= htmlspecialchars($empresa['razao_social'] ?? 'CASA FORTE') ?></p>
                <p>Página 1 de 1</p>
            </div>
        </div>

    </div>

</body>
</html>