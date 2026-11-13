<?php
// materiais.php
session_start();
require_once __DIR__ . '/src/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$pdo = Database::getInstance();
$material_edicao = null;

// --- PROCESSAMENTO DE AÇÕES ---

// 1. Criar Material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar') {
    $codigo = trim($_POST['codigo']);
    $descricao = trim($_POST['descricao']);
    $unidade = trim($_POST['unidade']);
    
    // Limpa a máscara de moeda (R$ 1.234,56 -> 1234.56)
    $preco_unitario = str_replace(['R$ ', '.', ','], ['', '', '.'], $_POST['preco_unitario']);
    if(empty($preco_unitario)) $preco_unitario = 0.00;
    
    // Limpa a máscara de porcentagem (5,00 -> 5.00)
    $ipi = str_replace(['%', ',', ' '], ['', '.', ''], $_POST['ipi']);
    if(empty($ipi)) $ipi = 0.00;

    $icms = str_replace(['%', ',', ' '], ['', '.', ''], $_POST['icms']);
    if(empty($icms)) $icms = 0.00;

    if ($codigo && $descricao && $unidade) {
        try {
            $stmt = $pdo->prepare("INSERT INTO materiais (codigo, descricao, unidade, preco_unitario, ipi, icms, ativo) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$codigo, $descricao, $unidade, (float)$preco_unitario, (float)$ipi, (float)$icms]);
            $_SESSION['sucesso'] = "Material cadastrado com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro: O código do material já está cadastrado.";
        }
    } else {
        $_SESSION['erro'] = "Preencha os campos obrigatórios (Código, Descrição e Unidade).";
    }
    header("Location: materiais.php");
    exit;
}

// 2. Alternar Status
if (isset($_GET['toggle'])) {
    $id_toggle = (int)$_GET['toggle'];
    $pdo->exec("UPDATE materiais SET ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END WHERE id = $id_toggle");
    $_SESSION['sucesso'] = "Status do material alterado.";
    header("Location: materiais.php");
    exit;
}

// 3. Editar Material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $id_edit = (int)$_POST['id'];
    $codigo = trim($_POST['codigo_edit']);
    $descricao = trim($_POST['descricao_edit']);
    $unidade = trim($_POST['unidade_edit']);
    
    $preco_unitario = str_replace(['R$ ', '.', ','], ['', '', '.'], $_POST['preco_unitario_edit']);
    if(empty($preco_unitario)) $preco_unitario = 0.00;
    
    $ipi = str_replace(['%', ',', ' '], ['', '.', ''], $_POST['ipi_edit']);
    if(empty($ipi)) $ipi = 0.00;
    
    $icms = str_replace(['%', ',', ' '], ['', '.', ''], $_POST['icms_edit']);
    if(empty($icms)) $icms = 0.00;
    
    try {
        $stmt = $pdo->prepare("UPDATE materiais SET codigo = ?, descricao = ?, unidade = ?, preco_unitario = ?, ipi = ?, icms = ? WHERE id = ?");
        $stmt->execute([$codigo, $descricao, $unidade, (float)$preco_unitario, (float)$ipi, (float)$icms, $id_edit]);
        $_SESSION['sucesso'] = "Material atualizado com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao atualizar: O código já está em uso.";
    }
    header("Location: materiais.php");
    exit;
}

// Captura dados para edição
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM materiais WHERE id = ?");
    $stmt->execute([$id_edit]);
    $material_edicao = $stmt->fetch();
}

// Filtro de Busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$sql = "SELECT * FROM materiais WHERE 1=1";
$params = [];

if ($busca) {
    $sql .= " AND (descricao LIKE ? OR codigo LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$sql .= " ORDER BY descricao ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$materiais = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="AST7 Softwares - Udson">
    <title>Materiais - Casa Forte</title>
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
        input:not(:placeholder-shown) { background-color: #F3F4F6 !important; }
        input:focus, textarea:focus { background-color: #FFFFFF !important; }
        .form-label { font-size: 0.7rem; text-transform: uppercase; color: #4B5563; font-weight: 700; letter-spacing: 0.05em; }
        
        ::placeholder {
            color: #D1D5DB !important;
            font-size: 0.75rem !important;
            text-transform: lowercase !important;
        }
    </style>
</head>
<body class="bg-bg-main min-h-screen flex flex-col font-sans text-text-main text-base">

    <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
        <img src="assets/ast7_logo.png" alt="AST7" class="h-10">
        <a href="dashboard.php" class="text-brand-blue hover:underline font-bold text-sm">Voltar ao Menu</a>
    </header>

    <main class="flex-grow p-4 md:p-6 max-w-7xl mx-auto w-full">
        
        <?php if(isset($_SESSION['erro'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 font-semibold text-sm"><?= $_SESSION['erro']; unset($_SESSION['erro']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['sucesso'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 font-semibold text-sm"><?= $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?></div>
        <?php endif; ?>

        <!-- FORMULÁRIO NO TOPO -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 mb-8">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Cadastrar Novo Material</h2>
            <!-- Alteração no Grid: Código (2), Descrição (5), Unidade (1), Preço (1), IPI (1), ICMS (1), Botão (1) = 12 -->
            <form method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <input type="hidden" name="action" value="criar">
                
                <div class="md:col-span-2">
                    <label class="form-label block mb-1">Código / Ref.</label>
                    <input type="text" name="codigo" required placeholder="Ex: BPP-GL-001" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold uppercase">
                </div>
                <div class="md:col-span-5">
                    <label class="form-label block mb-1">Descrição do Material</label>
                    <input type="text" name="descricao" required placeholder="Ex: Chapa de Aço 1020" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold uppercase">
                </div>
                <div class="md:col-span-1">
                    <label class="form-label block mb-1">Unid.</label>
                    <input type="text" name="unidade" required placeholder="KG, UN..." class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold uppercase">
                </div>
                <div class="md:col-span-1">
                    <label class="form-label block mb-1">R$ Unit.</label>
                    <input type="text" name="preco_unitario" id="preco_unitario" placeholder="R$ 0,00" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold text-right">
                </div>
                <div class="md:col-span-1">
                    <label class="form-label block mb-1">% IPI</label>
                    <input type="text" name="ipi" id="ipi" placeholder="0,00%" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold text-right">
                </div>
                <div class="md:col-span-1">
                    <label class="form-label block mb-1">% ICMS</label>
                    <input type="text" name="icms" id="icms" placeholder="0,00%" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold text-right">
                </div>

                <div class="md:col-span-1">
                    <button type="submit" class="w-full bg-brand-blue text-white font-bold py-1.5 px-2 rounded-lg shadow-md transition-all duration-200 hover:bg-blue-900 hover:-translate-y-0.5 active:scale-95 text-sm">
                        Salvar
                    </button>
                </div>
            </form>
        </div>

        <!-- LISTAGEM -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-bold text-gray-800">Materiais Cadastrados</h2>
                
                <form method="GET" class="flex gap-2 w-full md:w-auto">
                    <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="buscar por código ou descrição..." class="px-3 py-1.5 border border-gray-300 rounded text-sm font-semibold w-full md:w-80">
                    <button type="submit" class="bg-gray-200 text-gray-700 px-4 py-1.5 rounded hover:bg-gray-300 font-bold text-sm transition-colors">Buscar</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-300 text-gray-700 bg-gray-100">
                            <th class="py-2 px-3 font-bold">Código</th>
                            <th class="py-2 px-3 font-bold w-1/3">Descrição</th>
                            <th class="py-2 px-3 font-bold text-center">Unid.</th>
                            <th class="py-2 px-3 font-bold text-right">R$ Unitário</th>
                            <th class="py-2 px-3 font-bold text-right">% IPI</th>
                            <th class="py-2 px-3 font-bold text-right">% ICMS</th>
                            <th class="py-2 px-3 font-bold text-center">Status</th>
                            <th class="py-2 px-3 font-bold text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($materiais as $m): ?>
                        <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors <?= $m['ativo'] == 0 ? 'opacity-60 bg-gray-100' : '' ?>">
                            <td class="py-2 px-3 font-semibold text-gray-800"><?= htmlspecialchars($m['codigo']) ?></td>
                            <td class="py-2 px-3 text-gray-700 font-medium"><?= htmlspecialchars($m['descricao']) ?></td>
                            <td class="py-2 px-3 text-gray-600 text-center"><?= htmlspecialchars($m['unidade']) ?></td>
                            <td class="py-2 px-3 text-gray-600 text-right">R$ <?= number_format($m['preco_unitario'], 2, ',', '.') ?></td>
                            <td class="py-2 px-3 text-gray-600 text-right"><?= number_format($m['ipi'], 2, ',', '.') ?>%</td>
                            <td class="py-2 px-3 text-gray-600 text-right"><?= number_format($m['icms'], 2, ',', '.') ?>%</td>
                            <td class="py-2 px-3 text-center">
                                <?php if($m['ativo']): ?>
                                    <span class="text-green-600 font-bold">Ativo</span>
                                <?php else: ?>
                                    <span class="text-red-500 font-bold">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="materiais.php?edit=<?= $m['id'] ?>" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-3 py-1 rounded transition-colors text-xs font-medium">Editar</a>
                                    <a href="materiais.php?toggle=<?= $m['id'] ?>" class="<?= $m['ativo'] ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' ?> px-3 py-1 rounded transition-colors text-xs font-medium">
                                        <?= $m['ativo'] ? 'Desativar' : 'Ativar' ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($materiais)): ?>
                        <tr>
                            <td colspan="8" class="py-8 text-center text-gray-500 font-semibold">Nenhum material encontrado.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- MODAL DE EDIÇÃO -->
    <?php if($material_edicao): ?>
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 relative my-8">
            
            <a href="materiais.php" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 font-bold text-2xl leading-none">&times;</a>
            
            <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Editar Material: <?= htmlspecialchars($material_edicao['codigo']) ?></h2>
            
            <form method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="id" value="<?= $material_edicao['id'] ?>">
                
                <div class="md:col-span-3">
                    <label class="form-label block mb-1">Código / Ref.</label>
                    <input type="text" name="codigo_edit" required value="<?= htmlspecialchars($material_edicao['codigo']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold uppercase">
                </div>
                <div class="md:col-span-9">
                    <label class="form-label block mb-1">Descrição do Material</label>
                    <input type="text" name="descricao_edit" required value="<?= htmlspecialchars($material_edicao['descricao']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold uppercase">
                </div>
                
                <div class="md:col-span-2">
                    <label class="form-label block mb-1">Unidade</label>
                    <input type="text" name="unidade_edit" required value="<?= htmlspecialchars($material_edicao['unidade']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold uppercase">
                </div>
                <div class="md:col-span-4">
                    <label class="form-label block mb-1">R$ Unitário</label>
                    <input type="text" name="preco_unitario_edit" id="preco_unitario_edit" value="R$ <?= number_format($material_edicao['preco_unitario'], 2, ',', '.') ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold text-right">
                </div>
                <div class="md:col-span-3">
                    <label class="form-label block mb-1">% IPI</label>
                    <input type="text" name="ipi_edit" id="ipi_edit" value="<?= number_format($material_edicao['ipi'], 2, ',', '.') ?>%" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold text-right">
                </div>
                <div class="md:col-span-3">
                    <label class="form-label block mb-1">% ICMS</label>
                    <input type="text" name="icms_edit" id="icms_edit" value="<?= number_format($material_edicao['icms'], 2, ',', '.') ?>%" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-brand-blue font-semibold text-right">
                </div>

                <div class="md:col-span-12 pt-4 border-t border-gray-200 mt-4 flex justify-end gap-3">
                    <a href="materiais.php" class="bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold py-2 px-6 rounded-lg transition-colors text-sm flex items-center">
                        Fechar
                    </a>
                    <button type="submit" class="bg-brand-blue text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all hover:bg-blue-900 active:scale-95 text-sm">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <!-- SCRIPT PARA MÁSCARAS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            const currencyMaskOptions = {
                mask: 'R$ num',
                blocks: {
                    num: {
                        mask: Number,
                        thousandsSeparator: '.',
                        radix: ',',
                        scale: 2,
                        padFractionalZeros: true,
                        normalizeZeros: true,
                        mapToRadix: ['.']
                    }
                }
            };

            const percentMaskOptions = {
                mask: 'num%',
                blocks: {
                    num: {
                        mask: Number,
                        scale: 2,
                        padFractionalZeros: true,
                        radix: ','
                    }
                }
            };

            if(document.getElementById('preco_unitario')) IMask(document.getElementById('preco_unitario'), currencyMaskOptions);
            if(document.getElementById('ipi')) IMask(document.getElementById('ipi'), percentMaskOptions);
            if(document.getElementById('icms')) IMask(document.getElementById('icms'), percentMaskOptions);

            if(document.getElementById('preco_unitario_edit')) IMask(document.getElementById('preco_unitario_edit'), currencyMaskOptions);
            if(document.getElementById('ipi_edit')) IMask(document.getElementById('ipi_edit'), percentMaskOptions);
            if(document.getElementById('icms_edit')) IMask(document.getElementById('icms_edit'), percentMaskOptions);

        });
    </script>
</body>
</html>