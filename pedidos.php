<?php
// pedidos.php
session_start();
require_once __DIR__ . '/src/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$pdo = Database::getInstance();

// --- PAGINAÇÃO E BUSCA ---
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$pagina_atual = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$limite = 5;
$offset = ($pagina_atual - 1) * $limite;

// Filtro de data (Últimos 2 meses)
$data_limite = date('Y-m-d H:i:s', strtotime('-2 months'));

$sql_where = " WHERE p.data_pedido >= ? ";
$params = [$data_limite];

if ($busca) {
    $sql_where .= " AND (p.numero_pedido LIKE ? OR c.razao_social LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

// Conta total para paginação
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pedidos p LEFT JOIN clientes c ON p.cliente_id = c.id " . $sql_where);
$stmtCount->execute($params);
$total_registros = $stmtCount->fetchColumn();
$total_paginas = ceil($total_registros / $limite);

// Busca os pedidos
$sql = "SELECT p.*, c.razao_social as cliente_nome 
        FROM pedidos p 
        LEFT JOIN clientes c ON p.cliente_id = c.id " 
        . $sql_where . 
        " ORDER BY p.data_pedido DESC LIMIT $limite OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

if (!function_exists('urlPagina')) {
    function urlPagina($num) {
        global $busca;
        $q = [];
        if ($busca) $q['busca'] = $busca;
        $q['page'] = $num;
        return 'pedidos.php?' . http_build_query($q);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="AST7 Softwares - Udson">
    <title>Pedidos / Orçamentos - Casa Forte</title>
    <link rel="icon" type="image/png" href="assets/ast7_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
</head>
<body class="bg-bg-main min-h-screen flex flex-col font-sans text-text-main text-base">

    <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
        <img src="assets/ast7_logo.png" alt="AST7" class="h-10">
        <a href="dashboard.php" class="text-brand-blue hover:underline font-bold text-sm">Voltar ao Menu</a>
    </header>

    <main class="flex-grow p-4 md:p-6 max-w-7xl mx-auto w-full">
        
        <!-- BARRA SUPERIOR -->
        <div class="bg-white p-4 rounded-xl shadow-md border border-gray-200 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex gap-2 w-full md:w-auto">
                <h1 class="text-2xl font-bold text-gray-800 hidden md:block mr-4">Pedidos / Orçamentos</h1>
                
                <form method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                    <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="buscar por nº ou cliente..." class="px-3 py-2 border border-gray-300 rounded text-sm font-semibold w-full md:w-80">
                    <button type="submit" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 font-bold text-sm transition-colors">Buscar</button>
                    <?php if($busca): ?>
                        <a href="pedidos.php" class="bg-red-100 text-red-700 px-3 py-2 rounded hover:bg-red-200 font-bold text-sm transition-colors" title="Limpar Busca">X</a>
                    <?php endif; ?>
                </form>
            </div>

            <a href="novo_pedido.php" class="w-full md:w-auto bg-brand-blue text-white font-bold py-2 px-6 rounded-lg shadow-md hover:bg-blue-900 transition-colors flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Novo Pedido
            </a>
        </div>

        <!-- LISTAGEM -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-300 text-gray-700 bg-gray-100">
                            <th class="py-3 px-3 font-bold text-center w-24">Nº Pedido</th>
                            <th class="py-3 px-3 font-bold text-center w-32">Data</th>
                            <th class="py-3 px-3 font-bold">Cliente</th>
                            <th class="py-3 px-3 font-bold text-right">Total (R$)</th>
                            <th class="py-3 px-3 font-bold text-center">Status</th>
                            <th class="py-3 px-3 font-bold text-center w-24">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pedidos as $p): ?>
                        <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors">
                            <td class="py-3 px-3 text-center font-bold text-brand-blue">
                                <?= str_pad($p['numero_pedido'], 5, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td class="py-3 px-3 text-center text-gray-600">
                                <?= date('d/m/Y H:i', strtotime($p['data_pedido'])) ?>
                            </td>
                            <td class="py-3 px-3 font-semibold text-gray-800">
                                <?= htmlspecialchars($p['cliente_nome'] ?? 'Cliente não encontrado') ?>
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-gray-700">
                                <?= number_format($p['total_com_imposto'], 2, ',', '.') ?>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <?php if($p['status'] == 'orcamento'): ?>
                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs font-bold uppercase">Orçamento</span>
                                <?php elseif($p['status'] == 'pedido'): ?>
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold uppercase">Pedido</span>
                                <?php else: ?>
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold uppercase">Cancelado</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <a href="novo_pedido.php?id=<?= $p['id'] ?>" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-3 py-1.5 rounded transition-colors text-xs font-medium" title="Editar Pedido">Editar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($pedidos)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-500 font-semibold">Nenhum pedido encontrado nos últimos 2 meses.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- PAGINAÇÃO -->
            <?php if($total_paginas > 1): ?>
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex items-center justify-between sm:px-6">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Mostrando <span class="font-medium"><?= $offset + 1 ?></span> a <span class="font-medium"><?= min($offset + $limite, $total_registros) ?></span> de <span class="font-medium"><?= $total_registros ?></span> resultados
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <a href="<?= $pagina_atual > 1 ? urlPagina($pagina_atual - 1) : '#' ?>" class="<?= $pagina_atual > 1 ? 'bg-white text-gray-500 hover:bg-gray-50' : 'bg-gray-100 text-gray-300 cursor-not-allowed' ?> relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 text-sm font-medium">Anterior</a>
                            <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                                <a href="<?= urlPagina($i) ?>" class="<?= $i == $pagina_atual ? 'bg-brand-blue text-white border-brand-blue z-10' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium"><?= $i ?></a>
                            <?php endfor; ?>
                            <a href="<?= $pagina_atual < $total_paginas ? urlPagina($pagina_atual + 1) : '#' ?>" class="<?= $pagina_atual < $total_paginas ? 'bg-white text-gray-500 hover:bg-gray-50' : 'bg-gray-100 text-gray-300 cursor-not-allowed' ?> relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 text-sm font-medium">Próxima</a>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>