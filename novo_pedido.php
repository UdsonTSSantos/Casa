<?php
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/verifica_sessao.php'; 


if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$pdo = Database::getInstance();

$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pedido = null;
$itens_pedido = [];

if ($pedido_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();

    if ($pedido) {
        $stmt_itens = $pdo->prepare("
            SELECT pi.*, m.descricao, m.medida 
            FROM pedidos_itens pi 
            LEFT JOIN materiais m ON pi.material_id = m.id 
            WHERE pi.pedido_id = ?
        ");
        $stmt_itens->execute([$pedido_id]);
        $itens_pedido = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['erro'] = "Pedido não encontrado.";
        header("Location: pedidos.php");
        exit;
    }
}

$fornecedores = $pdo->query("SELECT id, razao_social, contato_nome FROM fornecedores WHERE ativo = 1 ORDER BY razao_social ASC")->fetchAll();
$clientes = $pdo->query("SELECT id, razao_social, contato_nome, endereco, numero, bairro, cidade, uf, cobranca_endereco, cobranca_numero, cobranca_bairro, cobranca_cidade, cobranca_uf, entrega_endereco, entrega_numero, entrega_bairro, entrega_cidade, entrega_uf FROM clientes WHERE ativo = 1 ORDER BY razao_social ASC")->fetchAll();
$vendedores = $pdo->query("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome ASC")->fetchAll();
$cifs = $pdo->query("SELECT id, razao_social FROM cifs WHERE ativo = 1 ORDER BY razao_social ASC")->fetchAll();
$materiais = $pdo->query("SELECT id, descricao, medida, preco_unitario, ipi, icms FROM materiais WHERE ativo = 1 ORDER BY descricao ASC")->fetchAll();

if ($pedido) {
    $numero_formatado = str_pad($pedido['numero_pedido'], 5, '0', STR_PAD_LEFT);
    $numero_pedido = $pedido['numero_pedido'];
} else {
    $ultimo_pedido = $pdo->query("SELECT MAX(numero_pedido) as max_num FROM pedidos")->fetch();
    $max_num = $ultimo_pedido['max_num'] ?? 0;
    $numero_pedido = ($max_num < 5000) ? 5000 : $max_num + 1;
    $numero_formatado = str_pad($numero_pedido, 5, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="AST7 Softwares - Udson">
    <title><?= $pedido ? 'Editar' : 'Novo' ?> Orçamento - Casa Forte</title>
    <link rel="icon" type="image/png" href="assets/ast7_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/imask"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { 'brand-blue': '#003B73', 'brand-orange': '#F97316', 'bg-main': '#F3F4F6' }
                }
            }
        }
    </script>
    <style>
        body { font-size: 13px; }
        .header-input, .table-input { transition: all 0.2s; }
        .header-input:focus, .table-input:focus, select:focus { 
            outline: none; box-shadow: 0 0 0 2px #003B73; background-color: white !important; border-color: transparent;
        }
        .header-input:not(:placeholder-shown) { background-color: #F3F4F6 !important; }
        .form-label { font-size: 0.7rem; text-transform: uppercase; color: #6B7280; font-weight: 700; letter-spacing: 0.05em; }
        .header-input { width: 100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 4px; font-weight: 600; text-transform: uppercase; color: #111827; }
        .header-input[readonly] { background-color: #F3F4F6; color: #4B5563; }
        .table-border th, .table-border td { border: 1px solid #E5E7EB; padding: 4px 8px; }
        .table-input { width: 100%; border: none; background: transparent; text-align: right; font-weight: 600; padding: 4px; color: #111827; }
        .input-material { color: #111827; font-weight: 600; }
        .is-zero { color: #D1D5DB !important; }
    </style>
</head>
<body class="bg-gray-200 min-h-screen font-sans text-gray-800">

    <div class="max-w-[1300px] mx-auto mt-4 px-2">
        <?php if(isset($_SESSION['erro'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-md mb-4 font-semibold text-sm">
                <?= $_SESSION['erro']; unset($_SESSION['erro']); ?>
            </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['sucesso'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-md mb-4 font-semibold text-sm">
                <?= $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
            </div>
        <?php endif; ?>
    </div>

    <form id="formPedido" action="salvar_pedido.php" method="POST" class="max-w-[1300px] mx-auto mb-6 bg-white shadow-xl relative">
        
        <!-- Botão Fechar (X) no canto superior direito -->
        <a href="pedidos.php" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 font-bold text-2xl leading-none z-10" title="Fechar e voltar">&times;</a>

        <input type="hidden" name="pedido_id" value="<?= $pedido_id ?>">
        
        <!-- CABEÇALHO -->
        <div class="p-4 md:p-6 border-b border-gray-300 grid grid-cols-1 md:grid-cols-12 gap-6 md:gap-8">
            
            <!-- Lado Esquerdo -->
            <div class="col-span-1 md:col-span-8 flex flex-col justify-between space-y-2">
                <div class="flex items-center gap-3">
                    <label class="w-20 form-label flex-shrink-0">Para:</label>
                    <select name="fornecedor_id" id="fornecedor_id" class="header-input flex-1 font-bold text-brand-blue">
                        <option value="">Selecione o Fornecedor...</option>
                        <?php foreach($fornecedores as $f): ?>
                            <option value="<?= $f['id'] ?>" data-contato="<?= htmlspecialchars($f['contato_nome']) ?>" <?= ($pedido && $pedido['fornecedor_id'] == $f['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['razao_social']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <label class="w-20 form-label flex-shrink-0">Att:</label>
                    <input type="text" name="fornecedor_att" id="fornecedor_att" value="<?= $pedido ? htmlspecialchars($pedido['fornecedor_att'] ?? '') : '' ?>" placeholder=" " class="header-input flex-1">
                </div>

                <div class="border-t border-gray-200 my-2"></div>

                <div class="flex items-center gap-3">
                    <label class="w-20 form-label flex-shrink-0">Cliente:</label>
                    <select name="cliente_id" id="cliente_id" required class="header-input flex-1 font-bold text-gray-800">
                        <option value="">Selecione o Cliente...</option>
                        <?php foreach($clientes as $c): ?>
                            <?php 
                                $end_cobranca = '';
                                if (!empty($c['cobranca_endereco'])) {
                                    $end_cobranca = $c['cobranca_endereco'] . ', ' . $c['cobranca_numero'] . ' - ' . $c['cobranca_bairro'] . ' - ' . $c['cobranca_cidade'] . '/' . $c['cobranca_uf'];
                                }
                                $end_entrega = '';
                                if (!empty($c['entrega_endereco'])) {
                                    $end_entrega = $c['entrega_endereco'] . ', ' . $c['entrega_numero'] . ' - ' . $c['entrega_bairro'] . ' - ' . $c['entrega_cidade'] . '/' . $c['entrega_uf'];
                                }
                            ?>
                            <option value="<?= $c['id'] ?>" 
                                    data-contato="<?= htmlspecialchars($c['contato_nome']) ?>" 
                                    data-cobranca="<?= htmlspecialchars($end_cobranca) ?>"
                                    data-entrega="<?= htmlspecialchars($end_entrega) ?>"
                                    <?= ($pedido && $pedido['cliente_id'] == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['razao_social']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <label class="w-20 form-label flex-shrink-0">Att:</label>
                    <input type="text" name="cliente_att" id="cliente_att" value="<?= $pedido ? htmlspecialchars($pedido['cliente_att'] ?? '') : '' ?>" placeholder=" " class="header-input flex-1">
                </div>
            </div>

            <!-- Lado Direito (Orçamento, Data, Endereços) -->
            <div class="col-span-1 md:col-span-4 flex flex-col justify-between space-y-4">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="form-label">Orçamento Nº:</div>
                        <input type="hidden" name="numero_pedido" value="<?= $numero_pedido ?>">
                        <div class="text-4xl font-extrabold text-brand-blue tracking-tight"><?= $numero_formatado ?></div>
                    </div>
                    <div class="text-right pt-1">
                        <div class="form-label">Data do orçamento:</div>
                        <div class="font-bold text-gray-700"><?= $pedido ? date('d/m/Y', strtotime($pedido['data_pedido'])) : date('d/m/Y') ?></div>
                        <div id="relogio" class="text-gray-500 text-xs font-medium"></div>
                    </div>
                </div>
                
                <!-- Endereços (Ocultos no mobile) -->
                <div class="hidden md:block">
                    <label class="form-label block mb-1">Endereço de cobrança:</label>
                    <input type="text" name="endereco_cobranca" id="endereco_cobranca" value="<?= $pedido ? htmlspecialchars($pedido['endereco_cobranca']) : '' ?>" placeholder=" " class="header-input w-full">
                </div>
                <div class="hidden md:block">
                    <label class="form-label block mb-1">Endereço de entrega:</label>
                    <input type="text" name="endereco_entrega" id="endereco_entrega" value="<?= $pedido ? htmlspecialchars($pedido['endereco_entrega']) : '' ?>" placeholder=" " class="header-input w-full">
                </div>
            </div>
        </div>

        <!-- TABELA DE ITENS (Com rolagem horizontal no mobile) -->
        <div class="p-4 w-full overflow-x-auto">
            <table class="w-full table-border text-xs min-w-[800px]" id="tabelaItens">
                <thead class="bg-brand-blue text-white">
                    <tr>
                        <th class="w-10 text-center">Item</th>
                        <th class="text-left">Material</th>
                        <th class="w-16 text-center">Medida</th>
                        <th class="w-20 text-center">QTD</th>
                        <th class="w-24 text-right">R$ Unit</th>
                        <th class="w-16 text-right">% IPI</th>
                        <th class="w-16 text-right">% ICMS</th>
                        <th class="w-24 text-right">Com imposto</th>
                        <th class="w-24 text-right">Sub total</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="9" class="p-2">
                            <button type="button" onclick="adicionarLinha()" class="bg-white text-gray-700 px-4 py-1.5 text-xs font-bold rounded border border-gray-300 hover:bg-gray-100 transition-colors shadow-sm">+ Adicionar Item</button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- RODAPÉ DE TOTAIS -->
        <div class="p-4 border-t border-gray-300 bg-gray-50 flex flex-col xl:flex-row justify-between items-center text-xs gap-4">
            <!-- Os 6 itens ficam ocultos até 1280px (xl) -->
            <div class="flex-1 w-full grid grid-cols-1 xl:grid-cols-7 gap-2 text-center">
                <div class="hidden xl:block"><div class="form-label">Número de itens</div><div id="num_itens" class="text-sm">000000</div></div>
                <div class="hidden xl:block"><div class="form-label">QTD de itens</div><div id="qtd_itens" class="text-sm is-zero">0</div></div>
                <div class="hidden xl:block"><div class="form-label">R$ dos impostos</div><div id="total_impostos" class="text-sm is-zero">0,00</div></div>
                <div class="hidden xl:block"><div class="form-label">Total sem impostos</div><div id="total_sem_imposto" class="text-sm is-zero">0,00</div></div>
                
                <!-- Total com impostos fica visível -->
                <div class="block"><div class="form-label">Total com impostos</div><div id="total_com_imposto" class="text-sm is-zero">0,00</div></div>
                
                <div class="hidden xl:block"><div class="form-label">R$ IPI</div><div id="total_ipi" class="text-sm is-zero">0,00</div></div>
                <div class="hidden xl:block"><div class="form-label">R$ ICMS</div><div id="total_icms" class="text-sm is-zero">0,00</div></div>
            </div>
            
            <!-- Total Geral sempre visível -->
            <div class="text-right w-full xl:w-auto pl-0 xl:pl-6 border-l-0 xl:border-l border-gray-300 min-w-[200px]">
                <div class="form-label">TOTAL | Sem imposto</div>
                <div class="text-4xl font-extrabold text-brand-blue tracking-tight">R$ <span id="display_total_geral" class="is-zero">0,00</span></div>
            </div>
        </div>

        <!-- CONDIÇÕES E OBSERVAÇÕES -->
        <div class="p-4 md:p-6 border-t border-gray-300 grid grid-cols-1 md:grid-cols-12 gap-6 text-xs bg-white">
            
            <div class="col-span-1 md:col-span-3 space-y-4">
                <!-- Vendedor (Oculto no mobile) -->
                <div class="hidden md:block">
                    <label class="form-label block mb-1">Vendedor</label>
                    <select name="vendedor_id" class="header-input w-full">
                        <?php foreach($vendedores as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= ($pedido && $pedido['vendedor_id'] == $v['id']) || (!$pedido && $v['id'] == $_SESSION['usuario_id']) ? 'selected' : '' ?>><?= htmlspecialchars($v['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="form-label block mb-1">Faturamento / Entrega</label>
                    <input type="text" name="faturamento" value="<?= $pedido ? htmlspecialchars($pedido['faturamento']) : 'IMEDIATA' ?>" class="header-input w-full">
                </div>
                
                <div class="space-y-2 mt-2">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_fob_fornecedor" id="is_fob_fornecedor" class="w-4 h-4 cursor-pointer" <?= ($pedido && $pedido['is_fob_fornecedor'] == 1) ? 'checked' : '' ?>>
                        <label for="is_fob_fornecedor" class="form-label mb-0 cursor-pointer">Fob Fornecedor</label>
                    </div>
                    <input type="text" name="fob_fornecedor_texto" id="fob_fornecedor_texto" value="<?= $pedido ? htmlspecialchars($pedido['fob_fornecedor_texto'] ?? '') : '' ?>" placeholder="Digite a informação..." class="header-input w-full">
                </div>
            </div>
            
            <div class="col-span-1 md:col-span-4 space-y-3">
                <div class="form-label text-gray-800 font-bold mb-2">CONDIÇÕES DE PAGAMENTO</div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="cond_faturado" id="cond_faturado" class="w-4 h-4 cursor-pointer" <?= ($pedido && !empty($pedido['condicao_faturado'])) ? 'checked' : '' ?>>
                    <label for="cond_faturado" class="form-label mb-0 cursor-pointer">Faturado</label>
                </div>
                <input type="text" name="condicao_faturado" id="condicao_faturado" value="<?= $pedido ? htmlspecialchars($pedido['condicao_faturado']) : '' ?>" placeholder="Digite a condição..." class="header-input w-full">
                <div class="flex items-center gap-2 mt-3">
                    <input type="checkbox" name="cond_avista" id="cond_avista" class="w-4 h-4 cursor-pointer" <?= ($pedido && !empty($pedido['condicao_avista'])) ? 'checked' : '' ?>>
                    <label for="cond_avista" class="form-label mb-0 cursor-pointer">À vista</label>
                </div>
                <input type="text" name="condicao_avista" value="<?= $pedido ? htmlspecialchars($pedido['condicao_avista']) : '' ?>" placeholder="Digite a condição..." class="header-input w-full">
                <div class="flex items-center gap-2 mt-3">
                    <input type="checkbox" name="cond_cif" id="cond_cif" class="w-4 h-4 cursor-pointer" <?= ($pedido && !empty($pedido['cif_id'])) ? 'checked' : '' ?>>
                    <label for="cond_cif" class="form-label mb-0 cursor-pointer">CIF</label>
                    <select name="cif_id" class="header-input flex-1">
                        <option value="">Selecione...</option>
                        <?php foreach($cifs as $cif): ?>
                            <option value="<?= $cif['id'] ?>" <?= ($pedido && $pedido['cif_id'] == $cif['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cif['razao_social']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Observação (Oculto no mobile) -->
            <div class="col-span-1 md:col-span-5 hidden md:block">
                <label class="form-label block mb-1">Observação</label>
                <textarea name="observacao" rows="6" placeholder=" " class="header-input w-full resize-none"><?= $pedido ? htmlspecialchars($pedido['observacao']) : '' ?></textarea>
            </div>
        </div>
        
        <!-- Botões (Fechar, Imprimir, Salvar) -->
        <div class="p-4 bg-gray-50 border-t border-gray-300 flex flex-col md:flex-row justify-end gap-3">
            <!-- Alterado de Cancelar para Fechar -->
            <a href="pedidos.php" class="bg-white border border-gray-300 text-gray-700 px-8 py-2 rounded font-bold hover:bg-gray-100 transition-colors text-center">Fechar</a>
            
            <?php if($pedido_id > 0): ?>
                <button type="button" onclick="abrirImpressao(<?= $pedido_id ?>)" class="bg-brand-orange text-white px-8 py-2 rounded font-bold hover:bg-orange-600 transition-colors shadow-md text-center flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Imprimir
                </button>
            <?php endif; ?>
            
            <button type="submit" class="bg-brand-blue text-white px-8 py-2 rounded font-bold hover:bg-blue-900 transition-colors shadow-md text-center">Salvar Pedido</button>
        </div>
    </form>

    <!-- ========================================== -->
    <!-- MODAL DE VISUALIZAÇÃO DE IMPRESSÃO         -->
    <!-- ========================================== -->
    <div id="modalImpressao" class="hidden fixed inset-0 bg-black bg-opacity-75 z-[9999] flex flex-col p-4 md:p-8">
        <div class="bg-white w-full max-w-[21cm] h-full mx-auto rounded-lg shadow-2xl relative flex flex-col overflow-hidden">
            <div class="bg-gray-100 px-4 py-3 flex justify-between items-center border-b">
                <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Visualização de Impressão
                </h3>
                <button onclick="fecharImpressao()" class="bg-red-500 hover:bg-red-600 text-white font-bold px-4 py-1.5 rounded shadow transition-colors flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    Fechar
                </button>
            </div>
            <div class="flex-1 overflow-auto bg-gray-300">
                <iframe id="iframeImpressao" src="" class="w-full h-full border-none"></iframe>
            </div>
        </div>
    </div>

    <template id="rowTemplate">
        <tr class="hover:bg-blue-50">
            <td class="text-center font-bold text-gray-500 item-number"></td>
            <td class="relative">
                <input type="text" name="item_material[]" list="lista_materiais" class="w-full border-none bg-transparent font-semibold uppercase input-material p-1 focus:outline-none" placeholder="Digite ou selecione..." onchange="buscarMaterial(this)">
                <input type="hidden" name="item_material_id[]" class="input-material-id">
            </td>
            <td><input type="text" name="item_medida[]" class="table-input text-center input-medida" value="KGS" onchange="calcularLinha(this)"></td>
            <td><input type="text" name="item_qtd[]" class="table-input text-center input-qtd is-zero" value="0" onchange="calcularLinha(this)" oninput="verificarZero(this)"></td>
            <td><input type="text" name="item_preco[]" class="table-input input-preco is-zero" value="0,00" onchange="calcularLinha(this)" oninput="verificarZero(this)"></td>
            <td><input type="text" name="item_ipi[]" class="table-input input-ipi is-zero" value="0,00" onchange="calcularLinha(this)" oninput="verificarZero(this)"></td>
            <td><input type="text" name="item_icms[]" class="table-input input-icms is-zero" value="0,00" onchange="calcularLinha(this)" oninput="verificarZero(this)"></td>
            <td class="text-right font-bold input-valor-com is-zero p-1">0,00</td>
            <td class="text-right font-bold text-gray-800 input-valor-sem is-zero p-1">0,00</td>
        </tr>
    </template>

    <datalist id="lista_materiais">
        <?php foreach($materiais as $m): ?>
            <option value="<?= htmlspecialchars($m['descricao']) ?>" data-id="<?= $m['id'] ?>" data-medida="<?= htmlspecialchars($m['medida']) ?>" data-preco="<?= $m['preco_unitario'] ?>" data-ipi="<?= $m['ipi'] ?>" data-icms="<?= $m['icms'] ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <script>
        // Função para abrir o modal de impressão
        function abrirImpressao(id) {
            const modal = document.getElementById('modalImpressao');
            const iframe = document.getElementById('iframeImpressao');
            iframe.src = 'imprimir_pedido.php?id=' + id;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        // Função para fechar o modal
        function fecharImpressao() {
            const modal = document.getElementById('modalImpressao');
            const iframe = document.getElementById('iframeImpressao');
            iframe.src = ''; 
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        const itensExistentes = <?= json_encode($itens_pedido) ?>;

        document.addEventListener('DOMContentLoaded', function() {
            setInterval(() => { document.getElementById('relogio').textContent = new Date().toLocaleTimeString('pt-BR'); }, 1000);

            <?php if($pedido): ?>
                atualizarAttFornecedor();
            <?php endif; ?>

            document.getElementById('fornecedor_id').addEventListener('change', atualizarAttFornecedor);

            document.getElementById('cliente_id').addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                document.getElementById('cliente_att').value = option.getAttribute('data-contato') || '';
                document.getElementById('endereco_cobranca').value = option.getAttribute('data-cobranca') || '';
                document.getElementById('endereco_entrega').value = option.getAttribute('data-entrega') || '';
            });

            if (itensExistentes.length > 0) {
                itensExistentes.forEach(item => { adicionarLinha(item); });
            } else {
                adicionarLinha(); adicionarLinha(); adicionarLinha();
            }
            
            atualizarNumeracao();
            calcularTotais();
        });

        function atualizarAttFornecedor() {
            const select = document.getElementById('fornecedor_id');
            const option = select.options[select.selectedIndex];
            if (option.value) {
                document.getElementById('fornecedor_att').value = option.getAttribute('data-contato') || '';
            }
        }

        function adicionarLinha(itemData = null) {
            const tbody = document.querySelector('#tabelaItens tbody');
            const template = document.getElementById('rowTemplate');
            const clone = template.content.cloneNode(true);

            if (itemData) {
                clone.querySelector('.input-material-id').value = itemData.material_id || '';
                clone.querySelector('.input-material').value = itemData.descricao_manual || itemData.descricao || '';
                clone.querySelector('.input-medida').value = itemData.medida || 'KGS';
                clone.querySelector('.input-qtd').value = parseFloat(itemData.quantidade).toFixed(0);
                clone.querySelector('.input-preco').value = parseFloat(itemData.preco_unitario).toFixed(2).replace('.', ',');
                clone.querySelector('.input-ipi').value = parseFloat(itemData.ipi).toFixed(2).replace('.', ',');
                clone.querySelector('.input-icms').value = parseFloat(itemData.icms).toFixed(2).replace('.', ',');
            }

            tbody.appendChild(clone);
            aplicarMascaras();
            
            if (itemData) {
                const lastRow = tbody.lastElementChild;
                calcularLinha(lastRow.querySelector('.input-qtd'));
            }
            
            const rows = tbody.querySelectorAll('tr');
            const lastRow = rows[rows.length - 1];
            lastRow.querySelectorAll('input, .input-valor-com, .input-valor-sem').forEach(el => {
                if(el.classList.contains('input-material')) return;
                verificarZero(el);
            });
        }

        function atualizarNumeracao() {
            const rows = document.querySelectorAll('#tabelaItens tbody tr');
            let count = 1;
            rows.forEach((row) => {
                const material = row.querySelector('.input-material').value.trim();
                const qtd = row.querySelector('.input-qtd').value;
                if (material !== '' && qtd !== '0' && qtd !== '0,00' && qtd !== '') {
                    row.querySelector('.item-number').textContent = count;
                    count++;
                } else {
                    row.querySelector('.item-number').textContent = '';
                }
            });
            document.getElementById('num_itens').textContent = String(count - 1).padStart(6, '0');
        }

        document.addEventListener('focusout', function(e) {
            if (e.target.closest('#tabelaItens tbody tr')) { atualizarNumeracao(); }
        });

        function aplicarMascaras() {
            const intMask = { mask: 'num', blocks: { num: { mask: Number, scale: 0, radix: ',' } } };
            const numMask = { mask: 'num', blocks: { num: { mask: Number, scale: 2, padFractionalZeros: true, radix: ',' } } };
            document.querySelectorAll('.input-qtd').forEach(el => { if (!el.maskRef) el.maskRef = IMask(el, intMask); });
            document.querySelectorAll('.input-preco, .input-ipi, .input-icms').forEach(el => { if (!el.maskRef) el.maskRef = IMask(el, numMask); });
        }

        function buscarMaterial(input) {
            const val = input.value;
            const options = document.querySelectorAll('#lista_materiais option');
            let found = false;
            for (let opt of options) {
                if (opt.value === val) {
                    const row = input.closest('tr');
                    row.querySelector('.input-material-id').value = opt.getAttribute('data-id');
                    row.querySelector('.input-medida').value = opt.getAttribute('data-medida');
                    row.querySelector('.input-preco').value = parseFloat(opt.getAttribute('data-preco')).toFixed(2).replace('.', ',');
                    row.querySelector('.input-ipi').value = parseFloat(opt.getAttribute('data-ipi')).toFixed(2).replace('.', ',');
                    row.querySelector('.input-icms').value = parseFloat(opt.getAttribute('data-icms')).toFixed(2).replace('.', ',');
                    found = true;
                    break;
                }
            }
            if (!found) { 
                input.closest('tr').querySelector('.input-material-id').value = ''; 
                input.closest('tr').querySelector('.input-medida').value = 'KGS'; 
            }
            calcularLinha(input);
        }

        function verificarZero(element) {
            let val = element.value || element.textContent;
            val = val.replace('.', '').replace(',', '.');
            const num = parseFloat(val) || 0;
            if (num === 0) { element.classList.add('is-zero'); } else { element.classList.remove('is-zero'); }
        }

        function calcularLinha(element) {
            const row = element.closest('tr');
            const qtd = parseFloat(row.querySelector('.input-qtd').value.replace('.', '').replace(',', '.')) || 0;
            const preco = parseFloat(row.querySelector('.input-preco').value.replace('.', '').replace(',', '.')) || 0;
            const ipi = parseFloat(row.querySelector('.input-ipi').value.replace('.', '').replace(',', '.')) || 0;
            const icms = parseFloat(row.querySelector('.input-icms').value.replace('.', '').replace(',', '.')) || 0;
            
            const semImposto = qtd * preco;
            const valorIPI = semImposto * (ipi / 100);
            const valorICMS = semImposto * (icms / 100);
            const comImposto = semImposto + valorIPI + valorICMS;
            
            const elValorSem = row.querySelector('.input-valor-sem');
            const elValorCom = row.querySelector('.input-valor-com');
            
            elValorSem.textContent = formatarMoeda(semImposto);
            elValorCom.textContent = formatarMoeda(comImposto);
            
            verificarZero(elValorSem);
            verificarZero(elValorCom);
            
            calcularTotais();
        }

        function calcularTotais() {
            const rows = document.querySelectorAll('#tabelaItens tbody tr');
            let totalQtd = 0, totalSem = 0, totalCom = 0, totalIPI = 0, totalICMS = 0;

            rows.forEach(row => {
                const qtd = parseFloat(row.querySelector('.input-qtd').value.replace('.', '').replace(',', '.')) || 0;
                const preco = parseFloat(row.querySelector('.input-preco').value.replace('.', '').replace(',', '.')) || 0;
                const ipi = parseFloat(row.querySelector('.input-ipi').value.replace('.', '').replace(',', '.')) || 0;
                const icms = parseFloat(row.querySelector('.input-icms').value.replace('.', '').replace(',', '.')) || 0;
                
                const semImposto = qtd * preco;
                const valorIPI = semImposto * (ipi / 100);
                const valorICMS = semImposto * (icms / 100);
                const comImposto = semImposto + valorIPI + valorICMS;

                totalQtd += qtd; totalSem += semImposto; totalCom += comImposto; totalIPI += valorIPI; totalICMS += valorICMS;
            });

            const elQtd = document.getElementById('qtd_itens');
            const elImp = document.getElementById('total_impostos');
            const elSem = document.getElementById('total_sem_imposto');
            const elCom = document.getElementById('total_com_imposto');
            const elIPI = document.getElementById('total_ipi');
            const elICMS = document.getElementById('total_icms');
            const elGeral = document.getElementById('display_total_geral');

            elQtd.textContent = String(totalQtd);
            elImp.textContent = formatarMoeda(totalIPI + totalICMS);
            elSem.textContent = formatarMoeda(totalSem);
            elCom.textContent = formatarMoeda(totalCom);
            elIPI.textContent = formatarMoeda(totalIPI);
            elICMS.textContent = formatarMoeda(totalICMS);
            elGeral.textContent = formatarMoeda(totalSem);

            [elQtd, elImp, elSem, elCom, elIPI, elICMS, elGeral].forEach(verificarZero);
        }

        function formatarMoeda(valor) { return valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    </script>
</body>
</html>