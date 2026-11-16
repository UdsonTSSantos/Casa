<?php
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/verifica_sessao.php'; 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Função para formatar CNPJ e CPF
function formatarCnpjCpf($valor) {
    if (empty($valor)) return '';
    
    // Remove qualquer caractere que não seja número
    $valor = preg_replace('/[^0-9]/', '', $valor);
    $len = strlen($valor);

    if ($len === 14) { // CNPJ
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $valor);
    } elseif ($len === 11) { // CPF
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $valor);
    }
    
    return $valor; // Retorna como está se não for 11 ou 14 dígitos
}

$pdo = Database::getInstance();

// --- PROCESSAMENTO DE AÇÕES ---

// 1. Criar Cliente Principal (Passo 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_principal') {
    $dados = [
        'razao_social' => trim($_POST['razao_social']),
        'cpf' => trim($_POST['cpf']),
        'cnpj' => trim($_POST['cnpj']),
        'insc_estadual' => trim($_POST['insc_estadual']),
        'cep' => trim($_POST['cep']),
        'endereco' => trim($_POST['endereco']),
        'numero' => trim($_POST['numero']),
        'complemento' => trim($_POST['complemento']),
        'bairro' => trim($_POST['bairro']),
        'cidade' => trim($_POST['cidade']),
        'uf' => trim($_POST['uf']),
        'contato_nome' => trim($_POST['contato_nome']),
        'contato_celular' => trim($_POST['contato_celular']),
        'contato_departamento' => trim($_POST['contato_departamento']),
        'telefone' => trim($_POST['telefone']),
        'celular' => trim($_POST['celular']),
        'whatsapp' => trim($_POST['whatsapp']),
        // Força e-mail em minúsculo
        'email' => strtolower(trim($_POST['email'])),
        'email_danfe' => strtolower(trim($_POST['email_danfe'])),
        'is_fornecedor' => 0,
        'observacao' => trim($_POST['observacao']),
        'material_comercializado' => trim($_POST['material_comercializado'])
    ];

    if ($dados['razao_social'] && ($dados['cpf'] || $dados['cnpj'])) {
        try {
            $colunas = implode(', ', array_keys($dados));
            $placeholders = implode(', ', array_fill(0, count($dados), '?'));
            $sql = "INSERT INTO clientes ($colunas, ativo) VALUES ($placeholders, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($dados));
            
            $novo_id = $pdo->lastInsertId();
            header("Location: clientes.php?add_cobranca=" . $novo_id);
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro ao cadastrar. Verifique os dados.";
            header("Location: clientes.php");
            exit;
        }
    } else {
        $_SESSION['erro'] = "Preencha a Razão Social e pelo menos um documento (CPF ou CNPJ).";
        header("Location: clientes.php");
        exit;
    }
}

// 2. Salvar Cobrança (Passo 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_cobranca') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("UPDATE clientes SET cobranca_endereco=?, cobranca_numero=?, cobranca_complemento=?, cobranca_bairro=?, cobranca_cidade=?, cobranca_uf=?, cobranca_cep=? WHERE id=?");
    $stmt->execute([
        trim($_POST['cobranca_endereco']), trim($_POST['cobranca_numero']), trim($_POST['cobranca_complemento']),
        trim($_POST['cobranca_bairro']), trim($_POST['cobranca_cidade']), trim($_POST['cobranca_uf']), trim($_POST['cobranca_cep']), $id
    ]);
    
    header("Location: clientes.php?add_entrega=" . $id);
    exit;
}

// 3. Salvar Entrega (Passo 3 - Final)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_entrega') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("UPDATE clientes SET entrega_endereco=?, entrega_numero=?, entrega_complemento=?, entrega_bairro=?, entrega_cidade=?, entrega_uf=?, entrega_cep=? WHERE id=?");
    $stmt->execute([
        trim($_POST['entrega_endereco']), trim($_POST['entrega_numero']), trim($_POST['entrega_complemento']),
        trim($_POST['entrega_bairro']), trim($_POST['entrega_cidade']), trim($_POST['entrega_uf']), trim($_POST['entrega_cep']), $id
    ]);
    
    $_SESSION['sucesso'] = "Cliente cadastrado com sucesso!";
    header("Location: clientes.php");
    exit;
}

// 4. Alternar Status
if (isset($_GET['toggle'])) {
    $id_toggle = (int)$_GET['toggle'];
    $pdo->exec("UPDATE clientes SET ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END WHERE id = $id_toggle");
    $_SESSION['sucesso'] = "Status do cliente alterado.";
    header("Location: clientes.php");
    exit;
}

// 5. Editar Completo (Modal de Edição)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $id_edit = (int)$_POST['id'];
    
    $campos_cliente = [
        'razao_social', 'cpf', 'cnpj', 'insc_estadual', 'endereco', 'numero', 'complemento', 
        'bairro', 'cidade', 'uf', 'cep', 'contato_nome', 'contato_celular', 'contato_departamento', 
        'telefone', 'celular', 'whatsapp', 'email', 'email_danfe', 
        'cobranca_endereco', 'cobranca_numero', 'cobranca_complemento', 'cobranca_bairro', 
        'cobranca_cidade', 'cobranca_uf', 'cobranca_cep', 'entrega_endereco', 'entrega_numero', 
        'entrega_complemento', 'entrega_bairro', 'entrega_cidade', 'entrega_uf', 'entrega_cep',
        'observacao', 'material_comercializado'
    ];
    
    $dados = [];
    foreach ($campos_cliente as $campo) {
        if ($campo === 'email' || $campo === 'email_danfe') {
            $dados[$campo] = isset($_POST[$campo . '_edit']) ? strtolower(trim($_POST[$campo . '_edit'])) : null;
        } else {
            $dados[$campo] = isset($_POST[$campo . '_edit']) ? trim($_POST[$campo . '_edit']) : null;
        }
    }
    
    try {
        $updates = [];
        foreach ($dados as $key => $val) {
            $updates[] = "$key = ?";
        }
        $sql = "UPDATE clientes SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $valores = array_values($dados);
        $valores[] = $id_edit;
        $stmt->execute($valores);
        $_SESSION['sucesso'] = "Cliente atualizado com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao atualizar.";
    }
    header("Location: clientes.php");
    exit;
}

// --- CAPTURA DE DADOS PARA OS MODAIS ---
$cliente_edicao = null;
$cliente_cobranca = null;
$cliente_entrega = null;

if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id_edit]);
    $cliente_edicao = $stmt->fetch();
}

if (isset($_GET['add_cobranca'])) {
    $id_cob = (int)$_GET['add_cobranca'];
    $stmt = $pdo->prepare("SELECT id, razao_social FROM clientes WHERE id = ?");
    $stmt->execute([$id_cob]);
    $cliente_cobranca = $stmt->fetch();
}

if (isset($_GET['add_entrega'])) {
    $id_ent = (int)$_GET['add_entrega'];
    $stmt = $pdo->prepare("SELECT id, razao_social FROM clientes WHERE id = ?");
    $stmt->execute([$id_ent]);
    $cliente_entrega = $stmt->fetch();
}

// --- PAGINAÇÃO E BUSCA ---
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$busca_cnpj = isset($_GET['busca_cnpj']) ? trim($_GET['busca_cnpj']) : '';
$pagina_atual = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$limite = 10;
$offset = ($pagina_atual - 1) * $limite;

$sql_where = " WHERE 1=1";
$params = [];

if ($busca) {
    $sql_where .= " AND razao_social LIKE ?";
    $params[] = "%$busca%";
}
if ($busca_cnpj) {
    $sql_where .= " AND (cnpj LIKE ? OR cpf LIKE ?)";
    $params[] = "%$busca_cnpj%";
    $params[] = "%$busca_cnpj%";
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM clientes" . $sql_where);
$stmtCount->execute($params);
$total_registros = $stmtCount->fetchColumn();
$total_paginas = ceil($total_registros / $limite);

$sql = "SELECT * FROM clientes" . $sql_where . " ORDER BY razao_social ASC LIMIT $limite OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

if (!function_exists('urlPagina')) {
    function urlPagina($num) {
        global $busca, $busca_cnpj;
        $q = [];
        if ($busca) $q['busca'] = $busca;
        if ($busca_cnpj) $q['busca_cnpj'] = $busca_cnpj;
        $q['page'] = $num;
        return 'clientes.php?' . http_build_query($q);
    }
}

// --- LÓGICA DA PAGINAÇÃO INTELIGENTE ---
$adjacents = 2;
$start = $pagina_atual - $adjacents;
$end = $pagina_atual + $adjacents;

if ($start < 1) { $end += abs($start) + 1; $start = 1; }
if ($end > $total_paginas) { $start -= ($end - $total_paginas); $end = $total_paginas; }
if ($start < 1) $start = 1;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="AST7 Softwares - Udson">
    <title>Clientes - Casa Forte</title>
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
        ::placeholder { color: #D1D5DB !important; font-size: 0.75rem !important; text-transform: lowercase !important; }
        
        .api-loading { border-color: #3B82F6 !important; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5); }
        .api-success { border-color: #10B981 !important; }
        .api-error { border-color: #EF4444 !important; }
    </style>
</head>
<body class="bg-bg-main min-h-screen flex flex-col font-sans text-text-main text-base">

    <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
        <img src="assets/ast7_logo.png" alt="AST7" class="h-10">
        <a href="dashboard.php" class="text-brand-blue hover:underline font-bold text-sm">Voltar ao Menu</a>
    </header>

    <main class="flex-grow p-4 md:p-6 max-w-[1400px] mx-auto w-full">
        
        <?php if(isset($_SESSION['erro'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 font-semibold text-sm"><?= $_SESSION['erro']; unset($_SESSION['erro']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['sucesso'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 font-semibold text-sm"><?= $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?></div>
        <?php endif; ?>

        <!-- BARRA SUPERIOR -->
        <div class="bg-white p-4 rounded-xl shadow-md border border-gray-200 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex gap-2 w-full md:w-auto">
                <h1 class="text-2xl font-bold text-gray-800 hidden md:block mr-4">Clientes</h1>
                
                <form method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                    <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="procurar por nome..." class="px-3 py-2 border border-gray-300 rounded text-sm font-semibold w-full md:w-64">
                    <input type="text" name="busca_cnpj" value="<?= htmlspecialchars($busca_cnpj) ?>" placeholder="procurar por cnpj/cpf..." class="px-3 py-2 border border-gray-300 rounded text-sm font-semibold w-full md:w-48">
                    <button type="submit" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 font-bold text-sm transition-colors">Buscar</button>
                    <?php if($busca || $busca_cnpj): ?>
                        <a href="clientes.php" class="bg-red-100 text-red-700 px-3 py-2 rounded hover:bg-red-200 font-bold text-sm transition-colors" title="Limpar Busca">X</a>
                    <?php endif; ?>
                </form>
            </div>

            <button onclick="document.getElementById('modalNovoCliente').classList.remove('hidden')" class="w-full md:w-auto bg-brand-blue text-white font-bold py-2 px-6 rounded-lg shadow-md hover:bg-blue-900 transition-colors flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Adicionar novo cliente
            </button>
        </div>

        <!-- LISTAGEM -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-300 text-gray-700 bg-gray-100">
                            <th class="py-3 px-3 font-bold w-1/2">Cliente</th>
                            <th class="py-3 px-3 font-bold">CNPJ / CPF</th>
                            <th class="py-3 px-3 font-bold">E-mail</th>
                            <th class="py-3 px-3 font-bold text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clientes as $c): ?>
                        <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors <?= $c['ativo'] == 0 ? 'opacity-60 bg-gray-100' : '' ?>">
                            <td class="py-3 px-3 font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-3 h-3 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                                <?= htmlspecialchars($c['razao_social']) ?>
                            </td>
                            <!-- Máscara aplicada aqui -->
                            <td class="py-3 px-3 text-gray-600"><?= formatarCnpjCpf($c['cnpj'] ?: $c['cpf']) ?></td>
                            
                            <!-- E-mail forçado em minúsculo -->
                            <td class="py-3 px-3 text-gray-600"><?= htmlspecialchars(strtolower($c['email'])) ?></td>
                            <td class="py-3 px-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="clientes.php?edit=<?= $c['id'] ?>" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-3 py-1.5 rounded transition-colors text-xs font-medium">Editar</a>
                                    <a href="clientes.php?toggle=<?= $c['id'] ?>" class="<?= $c['ativo'] ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' ?> px-3 py-1.5 rounded transition-colors text-xs font-medium">
                                        <?= $c['ativo'] ? 'Desativar' : 'Ativar' ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($clientes)): ?>
                        <tr>
                            <td colspan="4" class="py-8 text-center text-gray-500 font-semibold">Nenhum cliente encontrado.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- PAGINAÇÃO INTELIGENTE -->
            <?php if($total_paginas > 1): ?>
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4 sm:px-6">
                <div class="w-full md:w-auto text-center md:text-left">
                    <p class="text-sm text-gray-700">
                        Mostrando <span class="font-medium"><?= $offset + 1 ?></span> a <span class="font-medium"><?= min($offset + $limite, $total_registros) ?></span> de <span class="font-medium"><?= $total_registros ?></span> resultados
                    </p>
                </div>
                <div class="w-full md:w-auto overflow-x-auto">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <a href="<?= $pagina_atual > 1 ? urlPagina($pagina_atual - 1) : '#' ?>" class="<?= $pagina_atual > 1 ? 'bg-white text-gray-500 hover:bg-gray-50' : 'bg-gray-100 text-gray-300 cursor-not-allowed' ?> relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 text-sm font-medium">Anterior</a>
                        
                        <?php if($start > 1): ?>
                            <a href="<?= urlPagina(1) ?>" class="<?= 1 == $pagina_atual ? 'bg-brand-blue text-white border-brand-blue z-10' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">1</a>
                            <?php if($start > 2): ?>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for($i = $start; $i <= $end; $i++): ?>
                            <a href="<?= urlPagina($i) ?>" class="<?= $i == $pagina_atual ? 'bg-brand-blue text-white border-brand-blue z-10' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if($end < $total_paginas): ?>
                            <?php if($end < $total_paginas - 1): ?>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                            <?php endif; ?>
                            <a href="<?= urlPagina($total_paginas) ?>" class="<?= $total_paginas == $pagina_atual ? 'bg-brand-blue text-white border-brand-blue z-10' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium"><?= $total_paginas ?></a>
                        <?php endif; ?>

                        <a href="<?= $pagina_atual < $total_paginas ? urlPagina($pagina_atual + 1) : '#' ?>" class="<?= $pagina_atual < $total_paginas ? 'bg-white text-gray-500 hover:bg-gray-50' : 'bg-gray-100 text-gray-300 cursor-not-allowed' ?> relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 text-sm font-medium">Próxima</a>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- ========================================== -->
    <!-- MODAL 1: NOVO CLIENTE (Passo 1)            -->
    <!-- ========================================== -->
    <div id="modalNovoCliente" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-start justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-[1200px] p-4 md:p-6 relative my-8">
            <button onclick="document.getElementById('modalNovoCliente').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 font-bold text-2xl leading-none">&times;</button>
            <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Adicionar Novo Cliente (Passo 1 de 3)</h2>
            
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="criar_principal">
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1 text-brand-blue">CNPJ</label>
                        <input type="text" name="cnpj" id="cnpj_1" placeholder="Digite o CNPJ" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold transition-colors duration-300">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">CPF</label>
                        <input type="text" name="cpf" id="cpf_1" placeholder="000.000.000-00" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-6">
                        <label class="form-label block mb-1">Cliente *</label>
                        <input type="text" id="razao_social_1" name="razao_social" required placeholder="Razão Social / Nome" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-brand-blue font-bold text-lg uppercase text-brand-blue">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Insc. Est.</label>
                        <input type="text" name="insc_estadual" placeholder="Isento" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Telefone(s)</label>
                        <input type="text" name="telefone" id="telefone_1" placeholder="(11) 2000-0000" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Celular Principal</label>
                        <input type="text" name="celular" id="celular_1" placeholder="(11) 99999-9999" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1 text-green-600">WhatsApp</label>
                        <input type="text" name="whatsapp" id="whatsapp_1" placeholder="(11) 99999-9999" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Contato</label>
                        <input type="text" name="contato_nome" placeholder="Nome do contato" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Departamento</label>
                        <input type="text" name="contato_departamento" placeholder="Ex: Compras" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Celular (Contato)</label>
                        <input type="text" name="contato_celular" id="contato_celular_1" placeholder="(11) 99999-9999" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-6">
                        <label class="form-label block mb-1">E-mail</label>
                        <input type="email" name="email" id="email_1" placeholder="email@cliente.com.br" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold lowercase">
                    </div>
                    <div class="md:col-span-6">
                        <label class="form-label block mb-1">DANFE para</label>
                        <input type="email" name="email_danfe" id="email_danfe_1" placeholder="danfe@cliente.com.br" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold lowercase">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">CEP</label>
                        <input type="text" name="cep" id="cep_1" placeholder="00000-000" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-7">
                        <label class="form-label block mb-1">Endereço</label>
                        <input type="text" name="endereco" id="endereco_1" placeholder="Rua, Av..." class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">Nº</label>
                        <input type="text" name="numero" id="numero_1" placeholder="123" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Complemento</label>
                        <input type="text" name="complemento" id="complemento_1" placeholder="Compl." class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Bairro</label>
                        <input type="text" name="bairro" id="bairro_1" placeholder="Bairro" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Cidade</label>
                        <input type="text" name="cidade" id="cidade_1" placeholder="Cidade" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">UF</label>
                        <input type="text" name="uf" id="uf_1" maxlength="2" placeholder="SP" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label block mb-1">Observação</label>
                        <textarea name="observacao" rows="2" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></textarea>
                    </div>
                    <div>
                        <label class="form-label block mb-1">Material Comercializado</label>
                        <textarea name="material_comercializado" rows="2" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></textarea>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200 mt-4 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modalNovoCliente').classList.add('hidden')" class="bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold py-2 px-6 rounded-lg transition-colors text-sm">Cancelar</button>
                    <button type="submit" class="bg-brand-blue text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all hover:bg-blue-900 text-sm">Salvar e ir para Cobrança &rarr;</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: ENDEREÇO DE COBRANÇA (Passo 2)    -->
    <!-- ========================================== -->
    <?php if($cliente_cobranca): ?>
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-start justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-4 md:p-6 relative my-8">
            <a href="clientes.php" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 font-bold text-2xl leading-none">&times;</a>
            <h2 class="text-xl font-bold text-gray-800 mb-1">Endereço de Cobrança</h2>
            <p class="text-sm text-gray-600 mb-4">Cliente: <strong><?= htmlspecialchars($cliente_cobranca['razao_social']) ?></strong> (Passo 2 de 3)</p>
            
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="salvar_cobranca">
                <input type="hidden" name="id" value="<?= $cliente_cobranca['id'] ?>">
                
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-4"><label class="form-label block mb-1">CEP</label><input type="text" name="cobranca_cep" id="cobranca_cep" placeholder="00000-000" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                    <div class="col-span-8"><label class="form-label block mb-1">Endereço</label><input type="text" name="cobranca_endereco" id="cobranca_endereco" placeholder="Rua, Av..." class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase"></div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-4"><label class="form-label block mb-1">Número</label><input type="text" name="cobranca_numero" placeholder="123" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                    <div class="col-span-8"><label class="form-label block mb-1">Complemento</label><input type="text" name="cobranca_complemento" placeholder="Sala, Andar..." class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-6"><label class="form-label block mb-1">Bairro</label><input type="text" name="cobranca_bairro" id="cobranca_bairro" placeholder="Bairro" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase"></div>
                    <div class="col-span-6"><label class="form-label block mb-1">Cidade</label><input type="text" name="cobranca_cidade" id="cobranca_cidade" placeholder="Cidade" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase"></div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-2"><label class="form-label block mb-1">UF</label><input type="text" name="cobranca_uf" id="cobranca_uf" maxlength="2" placeholder="SP" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase"></div>
                </div>

                <div class="pt-4 border-t border-gray-200 mt-4 flex justify-between items-center">
                    <button type="submit" name="pular" value="1" class="text-gray-500 hover:text-gray-800 font-semibold text-sm underline">Pular esta etapa</button>
                    <button type="submit" class="bg-brand-blue text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all hover:bg-blue-900 text-sm">Salvar e ir para Entrega &rarr;</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- MODAL 3: ENDEREÇO DE ENTREGA (Passo 3)     -->
    <!-- ========================================== -->
    <?php if($cliente_entrega): ?>
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-start justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-4 md:p-6 relative my-8">
            <a href="clientes.php" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 font-bold text-2xl leading-none">&times;</a>
            <h2 class="text-xl font-bold text-gray-800 mb-1">Endereço de Entrega</h2>
            <p class="text-sm text-gray-600 mb-4">Cliente: <strong><?= htmlspecialchars($cliente_entrega['razao_social']) ?></strong> (Passo 3 de 3)</p>
            
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="salvar_entrega">
                <input type="hidden" name="id" value="<?= $cliente_entrega['id'] ?>">
                
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-4"><label class="form-label block mb-1">CEP</label><input type="text" name="entrega_cep" id="entrega_cep" placeholder="00000-000" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                    <div class="col-span-8"><label class="form-label block mb-1">Endereço</label><input type="text" name="entrega_endereco" id="entrega_endereco" placeholder="Rua, Av..." class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase"></div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-4"><label class="form-label block mb-1">Número</label><input type="text" name="entrega_numero" placeholder="123" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                    <div class="col-span-8"><label class="form-label block mb-1">Complemento</label><input type="text" name="entrega_complemento" placeholder="Sala, Andar..." class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-6"><label class="form-label block mb-1">Bairro</label><input type="text" name="entrega_bairro" id="entrega_bairro" placeholder="Bairro" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase"></div>
                    <div class="col-span-6"><label class="form-label block mb-1">Cidade</label><input type="text" name="entrega_cidade" id="entrega_cidade" placeholder="Cidade" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase"></div>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-2"><label class="form-label block mb-1">UF</label><input type="text" name="entrega_uf" id="entrega_uf" maxlength="2" placeholder="SP" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase"></div>
                </div>

                <div class="pt-4 border-t border-gray-200 mt-4 flex justify-between items-center">
                    <button type="submit" name="pular" value="1" class="text-gray-500 hover:text-gray-800 font-semibold text-sm underline">Pular e finalizar</button>
                    <button type="submit" class="bg-green-600 text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all hover:bg-green-700 text-sm">Finalizar Cadastro</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- MODAL 4: EDIÇÃO COMPLETA COM ABAS          -->
    <!-- ========================================== -->
    <?php if($cliente_edicao): ?>
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-start justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-[1200px] p-4 md:p-6 relative my-8">
            <a href="clientes.php" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 font-bold text-2xl leading-none">&times;</a>
            <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Editar Cliente Completo</h2>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="id" value="<?= $cliente_edicao['id'] ?>">
                
                <!-- NAVEGAÇÃO DAS ABAS -->
                <div class="border-b border-gray-200 mb-4">
                    <ul class="flex flex-wrap -mb-px text-sm font-bold text-center text-gray-500">
                        <li class="mr-2">
                            <button type="button" onclick="openTab(event, 'tab-dados')" class="tablinks inline-block p-3 border-b-2 border-brand-blue text-brand-blue rounded-t-lg">1. Dados do Cliente</button>
                        </li>
                        <li class="mr-2">
                            <button type="button" onclick="openTab(event, 'tab-cobranca')" class="tablinks inline-block p-3 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300 rounded-t-lg">2. Endereço de Cobrança</button>
                        </li>
                        <li class="mr-2">
                            <button type="button" onclick="openTab(event, 'tab-entrega')" class="tablinks inline-block p-3 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300 rounded-t-lg">3. Endereço de Entrega</button>
                        </li>
                    </ul>
                </div>

                <!-- CONTEÚDO DA ABA 1: DADOS DO CLIENTE -->
                <div id="tab-dados" class="tabcontent block space-y-4">
                    <!-- Dados Principais -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-5"><label class="form-label block mb-1">Cliente</label><input type="text" name="razao_social_edit" required value="<?= htmlspecialchars($cliente_edicao['razao_social']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase"></div>
                        <div class="md:col-span-2"><label class="form-label block mb-1">CPF</label><input type="text" name="cpf_edit" id="cpf_edit" value="<?= htmlspecialchars($cliente_edicao['cpf']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        <div class="md:col-span-3"><label class="form-label block mb-1 text-brand-blue">CNPJ</label><input type="text" name="cnpj_edit" id="cnpj_edit" value="<?= htmlspecialchars($cliente_edicao['cnpj']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold transition-colors duration-300"></div>
                        <div class="md:col-span-2"><label class="form-label block mb-1">Insc. Est.</label><input type="text" name="insc_estadual_edit" value="<?= htmlspecialchars($cliente_edicao['insc_estadual']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                    </div>

                    <!-- Contatos -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-3"><label class="form-label block mb-1">Contato</label><input type="text" name="contato_nome_edit" value="<?= htmlspecialchars($cliente_edicao['contato_nome']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        <div class="md:col-span-3"><label class="form-label block mb-1">Celular (Contato)</label><input type="text" name="contato_celular_edit" id="contato_celular_edit" value="<?= htmlspecialchars($cliente_edicao['contato_celular']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        <div class="md:col-span-3"><label class="form-label block mb-1">Depto</label><input type="text" name="contato_departamento_edit" value="<?= htmlspecialchars($cliente_edicao['contato_departamento']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        <div class="md:col-span-3"><label class="form-label block mb-1">Telefone</label><input type="text" name="telefone_edit" id="telefone_edit" value="<?= htmlspecialchars($cliente_edicao['telefone']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-3"><label class="form-label block mb-1">Celular Principal</label><input type="text" name="celular_edit" id="celular_edit" value="<?= htmlspecialchars($cliente_edicao['celular']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        <div class="md:col-span-3"><label class="form-label block mb-1">WhatsApp</label><input type="text" name="whatsapp_edit" id="whatsapp_edit" value="<?= htmlspecialchars($cliente_edicao['whatsapp']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        <div class="md:col-span-3"><label class="form-label block mb-1">E-mail</label><input type="email" name="email_edit" value="<?= htmlspecialchars(strtolower($cliente_edicao['email'])) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold lowercase"></div>
                        <div class="md:col-span-3"><label class="form-label block mb-1">DANFE para</label><input type="email" name="email_danfe_edit" value="<?= htmlspecialchars(strtolower($cliente_edicao['email_danfe'])) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold lowercase"></div>
                    </div>

                    <!-- Endereço Principal -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 border-t border-gray-100 pt-4 mt-2">
                        <div class="md:col-span-2"><label class="form-label block mb-1">CEP</label><input type="text" name="cep_edit" id="cep_edit" value="<?= htmlspecialchars($cliente_edicao['cep']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        <div class="md:col-span-6"><label class="form-label block mb-1">Endereço</label><input type="text" name="endereco_edit" id="endereco_edit" value="<?= htmlspecialchars($cliente_edicao['endereco']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        <div class="md:col-span-1"><label class="form-label block mb-1">Nº</label><input type="text" name="numero_edit" id="numero_edit" value="<?= htmlspecialchars($cliente_edicao['numero']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        <div class="md:col-span-3"><label class="form-label block mb-1">Compl.</label><input type="text" name="complemento_edit" id="complemento_edit" value="<?= htmlspecialchars($cliente_edicao['complemento']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                         <div class="md:col-span-4"><label class="form-label block mb-1">Bairro</label><input type="text" name="bairro_edit" id="bairro_edit" value="<?= htmlspecialchars($cliente_edicao['bairro']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                         <div class="md:col-span-6"><label class="form-label block mb-1">Cidade</label><input type="text" name="cidade_edit" id="cidade_edit" value="<?= htmlspecialchars($cliente_edicao['cidade']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                         <div class="md:col-span-2"><label class="form-label block mb-1">UF</label><input type="text" name="uf_edit" id="uf_edit" maxlength="2" value="<?= htmlspecialchars($cliente_edicao['uf']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                    </div>

                    <!-- Observações -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <div><label class="form-label block mb-1">Observação</label><textarea name="observacao_edit" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded font-semibold"><?= htmlspecialchars($cliente_edicao['observacao']) ?></textarea></div>
                        <div><label class="form-label block mb-1">Material Comercializado</label><textarea name="material_comercializado_edit" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded font-semibold"><?= htmlspecialchars($cliente_edicao['material_comercializado']) ?></textarea></div>
                    </div>
                </div>

                <!-- CONTEÚDO DA ABA 2: COBRANÇA -->
                <div id="tab-cobranca" class="tabcontent hidden space-y-4">
                    <div class="border border-gray-200 p-4 rounded-lg bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Endereço de Cobrança</h3>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
                            <div class="md:col-span-3"><label class="form-label block mb-1">CEP</label><input type="text" name="cobranca_cep_edit" id="cobranca_cep_edit" value="<?= htmlspecialchars($cliente_edicao['cobranca_cep']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                            <div class="md:col-span-7"><label class="form-label block mb-1">Endereço</label><input type="text" name="cobranca_endereco_edit" id="cobranca_endereco_edit" value="<?= htmlspecialchars($cliente_edicao['cobranca_endereco']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                            <div class="md:col-span-2"><label class="form-label block mb-1">Nº</label><input type="text" name="cobranca_numero_edit" value="<?= htmlspecialchars($cliente_edicao['cobranca_numero']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
                            <div class="md:col-span-4"><label class="form-label block mb-1">Bairro</label><input type="text" name="cobranca_bairro_edit" id="cobranca_bairro_edit" value="<?= htmlspecialchars($cliente_edicao['cobranca_bairro']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                            <div class="md:col-span-6"><label class="form-label block mb-1">Cidade</label><input type="text" name="cobranca_cidade_edit" id="cobranca_cidade_edit" value="<?= htmlspecialchars($cliente_edicao['cobranca_cidade']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                            <div class="md:col-span-2"><label class="form-label block mb-1">UF</label><input type="text" name="cobranca_uf_edit" id="cobranca_uf_edit" maxlength="2" value="<?= htmlspecialchars($cliente_edicao['cobranca_uf']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-8"><label class="form-label block mb-1">Complemento</label><input type="text" name="cobranca_complemento_edit" value="<?= htmlspecialchars($cliente_edicao['cobranca_complemento']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        </div>
                    </div>
                </div>

                <!-- CONTEÚDO DA ABA 3: ENTREGA -->
                <div id="tab-entrega" class="tabcontent hidden space-y-4">
                    <div class="border border-gray-200 p-4 rounded-lg bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Endereço de Entrega</h3>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
                            <div class="md:col-span-3"><label class="form-label block mb-1">CEP</label><input type="text" name="entrega_cep_edit" id="entrega_cep_edit" value="<?= htmlspecialchars($cliente_edicao['entrega_cep']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                            <div class="md:col-span-7"><label class="form-label block mb-1">Endereço</label><input type="text" name="entrega_endereco_edit" id="entrega_endereco_edit" value="<?= htmlspecialchars($cliente_edicao['entrega_endereco']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                            <div class="md:col-span-2"><label class="form-label block mb-1">Nº</label><input type="text" name="entrega_numero_edit" value="<?= htmlspecialchars($cliente_edicao['entrega_numero']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
                            <div class="md:col-span-4"><label class="form-label block mb-1">Bairro</label><input type="text" name="entrega_bairro_edit" id="entrega_bairro_edit" value="<?= htmlspecialchars($cliente_edicao['entrega_bairro']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                            <div class="md:col-span-6"><label class="form-label block mb-1">Cidade</label><input type="text" name="entrega_cidade_edit" id="entrega_cidade_edit" value="<?= htmlspecialchars($cliente_edicao['entrega_cidade']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                            <div class="md:col-span-2"><label class="form-label block mb-1">UF</label><input type="text" name="entrega_uf_edit" id="entrega_uf_edit" maxlength="2" value="<?= htmlspecialchars($cliente_edicao['entrega_uf']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-8"><label class="form-label block mb-1">Complemento</label><input type="text" name="entrega_complemento_edit" value="<?= htmlspecialchars($cliente_edicao['entrega_complemento']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold"></div>
                        </div>
                    </div>
                </div>

                <!-- BOTÕES FIXOS NO RODAPÉ -->
                <div class="pt-4 border-t border-gray-200 mt-6 flex justify-end gap-3">
                    <a href="clientes.php" class="bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold py-2 px-6 rounded-lg transition-colors text-sm flex items-center">Fechar</a>
                    <button type="submit" class="bg-brand-blue text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all hover:bg-blue-900 text-sm">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <!-- SCRIPT PARA MÁSCARAS, VIACEP E API CNPJ -->
    <script>
        // Função para alternar as abas no Modal de Edição
        function openTab(evt, tabName) {
            let i, tabcontent, tablinks;
            
            // Esconde todas as abas
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            
            // Remove a classe ativa de todos os botões
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" border-brand-blue text-brand-blue", " border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300");
            }
            
            // Mostra a aba atual
            document.getElementById(tabName).style.display = "block";
            
            // Adiciona a classe ativa ao botão clicado
            evt.currentTarget.className = evt.currentTarget.className.replace(" border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300", " border-brand-blue text-brand-blue");
        }

        document.addEventListener('DOMContentLoaded', function() {
            
            async function buscarCEP(cepValue, targetFields) {
                const cleanCep = cepValue.replace(/\D/g, '');
                if (cleanCep.length !== 8) return;
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
                    const data = await response.json();
                    if (!data.erro) {
                        if (targetFields.endereco && document.getElementById(targetFields.endereco)) document.getElementById(targetFields.endereco).value = data.logradouro;
                        if (targetFields.bairro && document.getElementById(targetFields.bairro)) document.getElementById(targetFields.bairro).value = data.bairro;
                        if (targetFields.cidade && document.getElementById(targetFields.cidade)) document.getElementById(targetFields.cidade).value = data.localidade;
                        if (targetFields.uf && document.getElementById(targetFields.uf)) document.getElementById(targetFields.uf).value = data.uf;
                    }
                } catch (error) { console.error("Erro CEP:", error); }
            }

            async function buscarCNPJ(cnpjInput, isEdit = false) {
                const cnpjValue = cnpjInput.value.replace(/\D/g, '');
                if (cnpjValue.length !== 14) return;

                const suffix = isEdit ? '_edit' : '_1';
                const idRazao = 'razao_social' + suffix;
                const idCep = 'cep' + suffix;
                const idEndereco = 'endereco' + suffix;
                const idNumero = 'numero' + suffix;
                const idComplemento = 'complemento' + suffix;
                const idBairro = 'bairro' + suffix;
                const idCidade = 'cidade' + suffix;
                const idUf = 'uf' + suffix;
                const idTelefone = 'telefone' + suffix;
                const idEmail = 'email' + suffix;

                cnpjInput.classList.add('api-loading');
                
                try {
                    const response = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpjValue}`);
                    if (!response.ok) throw new Error('CNPJ não encontrado');
                    const data = await response.json();

                    const setVal = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) el.value = val || '';
                    };

                    setVal(idRazao, data.razao_social);
                    setVal(idCep, data.cep);
                    setVal(idEndereco, data.logradouro);
                    setVal(idNumero, data.numero);
                    setVal(idComplemento, data.complemento);
                    setVal(idBairro, data.bairro);
                    setVal(idCidade, data.municipio);
                    setVal(idUf, data.uf);
                    if (data.ddd_telefone_1) setVal(idTelefone, data.ddd_telefone_1);
                    if (data.email) setVal(idEmail, data.email.toLowerCase());

                    cnpjInput.classList.remove('api-loading');
                    cnpjInput.classList.add('api-success');
                    setTimeout(() => cnpjInput.classList.remove('api-success'), 2000);

                } catch (error) {
                    console.warn('Erro na busca de CNPJ:', error.message);
                    cnpjInput.classList.remove('api-loading');
                    cnpjInput.classList.add('api-error');
                    setTimeout(() => cnpjInput.classList.remove('api-error'), 2000);
                }
            }

            const phoneMaskOptions = { mask: [{ mask: '(00) 0000-0000' }, { mask: '(00) 00000-0000' }] };
            const cnpjMaskOptions = { mask: 'XX.XXX.XXX/XXXX-XX', definitions: { 'X': /[a-zA-Z0-9]/ } };

            if(document.getElementById('cnpj_1')) {
                IMask(document.getElementById('cnpj_1'), cnpjMaskOptions);
                document.getElementById('cnpj_1').addEventListener('blur', function() { buscarCNPJ(this, false); });
            }
            if(document.getElementById('cpf_1')) IMask(document.getElementById('cpf_1'), { mask: '000.000.000-00' });
            if(document.getElementById('telefone_1')) IMask(document.getElementById('telefone_1'), { mask: '(00) 0000-0000' });
            if(document.getElementById('celular_1')) IMask(document.getElementById('celular_1'), phoneMaskOptions);
            if(document.getElementById('whatsapp_1')) IMask(document.getElementById('whatsapp_1'), phoneMaskOptions);
            if(document.getElementById('contato_celular_1')) IMask(document.getElementById('contato_celular_1'), phoneMaskOptions);
            
            if(document.getElementById('cep_1')) {
                IMask(document.getElementById('cep_1'), { mask: '00000-000' });
                document.getElementById('cep_1').addEventListener('blur', function() {
                    buscarCEP(this.value, { endereco: 'endereco_1', bairro: 'bairro_1', cidade: 'cidade_1', uf: 'uf_1' });
                });
            }

            if(document.getElementById('cobranca_cep')) {
                IMask(document.getElementById('cobranca_cep'), { mask: '00000-000' });
                document.getElementById('cobranca_cep').addEventListener('blur', function() {
                    buscarCEP(this.value, { endereco: 'cobranca_endereco', bairro: 'cobranca_bairro', cidade: 'cobranca_cidade', uf: 'cobranca_uf' });
                });
            }

            if(document.getElementById('entrega_cep')) {
                IMask(document.getElementById('entrega_cep'), { mask: '00000-000' });
                document.getElementById('entrega_cep').addEventListener('blur', function() {
                    buscarCEP(this.value, { endereco: 'entrega_endereco', bairro: 'entrega_bairro', cidade: 'entrega_cidade', uf: 'entrega_uf' });
                });
            }

            if(document.getElementById('cnpj_edit')) {
                IMask(document.getElementById('cnpj_edit'), cnpjMaskOptions);
                document.getElementById('cnpj_edit').addEventListener('blur', function() { buscarCNPJ(this, true); });
            }
            if(document.getElementById('cep_edit')) {
                IMask(document.getElementById('cep_edit'), { mask: '00000-000' });
                document.getElementById('cep_edit').addEventListener('blur', function() {
                    buscarCEP(this.value, { endereco: 'endereco_edit', bairro: 'bairro_edit', cidade: 'cidade_edit', uf: 'uf_edit' });
                });
            }
            if(document.getElementById('cobranca_cep_edit')) {
                IMask(document.getElementById('cobranca_cep_edit'), { mask: '00000-000' });
                document.getElementById('cobranca_cep_edit').addEventListener('blur', function() {
                    buscarCEP(this.value, { endereco: 'cobranca_endereco_edit', bairro: 'cobranca_bairro_edit', cidade: 'cobranca_cidade_edit', uf: 'cobranca_uf_edit' });
                });
            }
            if(document.getElementById('entrega_cep_edit')) {
                IMask(document.getElementById('entrega_cep_edit'), { mask: '00000-000' });
                document.getElementById('entrega_cep_edit').addEventListener('blur', function() {
                    buscarCEP(this.value, { endereco: 'entrega_endereco_edit', bairro: 'entrega_bairro_edit', cidade: 'entrega_cidade_edit', uf: 'entrega_uf_edit' });
                });
            }

            if(document.getElementById('cpf_edit')) IMask(document.getElementById('cpf_edit'), { mask: '000.000.000-00' });
            if(document.getElementById('telefone_edit')) IMask(document.getElementById('telefone_edit'), { mask: '(00) 0000-0000' });
            if(document.getElementById('contato_celular_edit')) IMask(document.getElementById('contato_celular_edit'), phoneMaskOptions);
            if(document.getElementById('celular_edit')) IMask(document.getElementById('celular_edit'), phoneMaskOptions);
            if(document.getElementById('whatsapp_edit')) IMask(document.getElementById('whatsapp_edit'), phoneMaskOptions);

        });
    </script>
</body>
</html>