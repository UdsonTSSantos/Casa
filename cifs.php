<?php
// cifs.php
session_start();
require_once __DIR__ . '/src/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$pdo = Database::getInstance();
$cif_edicao = null;

// --- PROCESSAMENTO DE AÇÕES ---

// 1. Criar CIF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar') {
    $razao_social = trim($_POST['razao_social']);
    $cnpj = trim($_POST['cnpj']);
    
    if ($razao_social && $cnpj) {
        try {
            // Bloqueia CNPJ vazio ou duplicado (o banco também tem UNIQUE)
            if (empty($cnpj)) {
                throw new Exception("O CNPJ é obrigatório.");
            }

            $stmt = $pdo->prepare("INSERT INTO cifs (razao_social, cpf, cnpj, insc_estadual, endereco, numero, complemento, bairro, cidade, uf, cep, telefone, celular, whatsapp, contato_nome, contato_celular, contato_departamento, contato_ramal, email, email_danfe, observacao, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $razao_social, trim($_POST['cpf']), $cnpj, trim($_POST['insc_estadual']),
                trim($_POST['endereco']), trim($_POST['numero']), trim($_POST['complemento']),
                trim($_POST['bairro']), trim($_POST['cidade']), trim($_POST['uf']), trim($_POST['cep']),
                trim($_POST['telefone']), trim($_POST['celular']), trim($_POST['whatsapp']),
                trim($_POST['contato_nome']), trim($_POST['contato_celular']), trim($_POST['contato_departamento']), trim($_POST['contato_ramal']),
                trim($_POST['email']), trim($_POST['email_danfe']), trim($_POST['observacao'])
            ]);
            $_SESSION['sucesso'] = "CIF cadastrado com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro: Este CNPJ já está cadastrado no sistema.";
        } catch (Exception $e) {
            $_SESSION['erro'] = $e->getMessage();
        }
    } else {
        $_SESSION['erro'] = "Os campos Nome e CNPJ são obrigatórios.";
    }
    header("Location: cifs.php");
    exit;
}

// 2. Alternar Status
if (isset($_GET['toggle'])) {
    $id_toggle = (int)$_GET['toggle'];
    $pdo->exec("UPDATE cifs SET ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END WHERE id = $id_toggle");
    $_SESSION['sucesso'] = "Status do CIF alterado.";
    header("Location: cifs.php");
    exit;
}

// 3. Excluir CIF
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    $pdo->exec("DELETE FROM cifs WHERE id = $id_del");
    $_SESSION['sucesso'] = "CIF excluído com sucesso.";
    header("Location: cifs.php");
    exit;
}

// 4. Editar CIF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $id_edit = (int)$_POST['id'];
    $razao_social = trim($_POST['razao_social']);
    $cnpj = trim($_POST['cnpj']);
    
    if ($razao_social && $cnpj) {
        try {
            $stmt = $pdo->prepare("UPDATE cifs SET razao_social=?, cpf=?, cnpj=?, insc_estadual=?, endereco=?, numero=?, complemento=?, bairro=?, cidade=?, uf=?, cep=?, telefone=?, celular=?, whatsapp=?, contato_nome=?, contato_celular=?, contato_departamento=?, contato_ramal=?, email=?, email_danfe=?, observacao=? WHERE id=?");
            $stmt->execute([
                $razao_social, trim($_POST['cpf']), $cnpj, trim($_POST['insc_estadual']),
                trim($_POST['endereco']), trim($_POST['numero']), trim($_POST['complemento']),
                trim($_POST['bairro']), trim($_POST['cidade']), trim($_POST['uf']), trim($_POST['cep']),
                trim($_POST['telefone']), trim($_POST['celular']), trim($_POST['whatsapp']),
                trim($_POST['contato_nome']), trim($_POST['contato_celular']), trim($_POST['contato_departamento']), trim($_POST['contato_ramal']),
                trim($_POST['email']), trim($_POST['email_danfe']), trim($_POST['observacao']),
                $id_edit
            ]);
            $_SESSION['sucesso'] = "CIF atualizado com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro: O CNPJ informado já pertence a outro CIF.";
        }
    }
    header("Location: cifs.php");
    exit;
}

// Captura dados para o modal de edição
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM cifs WHERE id = ?");
    $stmt->execute([$id_edit]);
    $cif_edicao = $stmt->fetch();
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
    $sql_where .= " AND cnpj LIKE ?";
    $params[] = "%$busca_cnpj%";
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM cifs" . $sql_where);
$stmtCount->execute($params);
$total_registros = $stmtCount->fetchColumn();
$total_paginas = ceil($total_registros / $limite);

$sql = "SELECT * FROM cifs" . $sql_where . " ORDER BY razao_social ASC LIMIT $limite OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cifs = $stmt->fetchAll();

function urlPagina($num) {
    global $busca, $busca_cnpj;
    $q = [];
    if ($busca) $q['busca'] = $busca;
    if ($busca_cnpj) $q['busca_cnpj'] = $busca_cnpj;
    $q['page'] = $num;
    return 'cifs.php?' . http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="AST7 Softwares - Udson">
    <title>CIF - Casa Forte</title>
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
                <h1 class="text-2xl font-bold text-gray-800 hidden md:block mr-4">CIF</h1>
                
                <form method="GET" class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                    <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="procurar por nome..." class="px-3 py-2 border border-gray-300 rounded text-sm font-semibold w-full md:w-64">
                    <input type="text" name="busca_cnpj" value="<?= htmlspecialchars($busca_cnpj) ?>" placeholder="procurar por cnpj..." class="px-3 py-2 border border-gray-300 rounded text-sm font-semibold w-full md:w-48">
                    <button type="submit" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 font-bold text-sm transition-colors">Buscar</button>
                    <?php if($busca || $busca_cnpj): ?>
                        <a href="cifs.php" class="bg-red-100 text-red-700 px-3 py-2 rounded hover:bg-red-200 font-bold text-sm transition-colors" title="Limpar Busca">X</a>
                    <?php endif; ?>
                </form>
            </div>

            <button onclick="document.getElementById('modalNovoCif').classList.remove('hidden')" class="w-full md:w-auto bg-brand-blue text-white font-bold py-2 px-6 rounded-lg shadow-md hover:bg-blue-900 transition-colors flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Adicionar novo CIF
            </button>
        </div>

        <!-- LISTAGEM -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-300 text-gray-700 bg-gray-100">
                            <th class="py-3 px-3 font-bold w-1/2">Nome da Empresa</th>
                            <th class="py-3 px-3 font-bold">CNPJ</th>
                            <th class="py-3 px-3 font-bold">Telefone</th>
                            <th class="py-3 px-3 font-bold text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cifs as $c): ?>
                        <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors <?= $c['ativo'] == 0 ? 'opacity-60 bg-gray-100' : '' ?>">
                            <td class="py-3 px-3 font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                <?= htmlspecialchars($c['razao_social']) ?>
                            </td>
                            <td class="py-3 px-3 text-gray-600"><?= htmlspecialchars($c['cnpj']) ?></td>
                            <td class="py-3 px-3 text-gray-600"><?= htmlspecialchars($c['telefone']) ?></td>
                            <td class="py-3 px-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="cifs.php?edit=<?= $c['id'] ?>" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-3 py-1.5 rounded transition-colors text-xs font-medium">Editar</a>
                                    <a href="cifs.php?toggle=<?= $c['id'] ?>" class="<?= $c['ativo'] ? 'bg-orange-100 text-orange-700 hover:bg-orange-200' : 'bg-green-100 text-green-700 hover:bg-green-200' ?> px-3 py-1.5 rounded transition-colors text-xs font-medium">
                                        <?= $c['ativo'] ? 'Desativar' : 'Ativar' ?>
                                    </a>
                                    <a href="cifs.php?delete=<?= $c['id'] ?>" onclick="return confirm('Tem certeza que deseja EXCLUIR este CIF?')" class="bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1.5 rounded transition-colors text-xs font-medium">Excluir</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($cifs)): ?>
                        <tr>
                            <td colspan="4" class="py-8 text-center text-gray-500 font-semibold">Nenhum CIF encontrado.</td>
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

    <!-- ========================================== -->
    <!-- MODAL: NOVO CIF                            -->
    <!-- ========================================== -->
    <div id="modalNovoCif" class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl p-6 relative my-8">
            <button onclick="document.getElementById('modalNovoCif').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 font-bold text-2xl leading-none">&times;</button>
            
            <div class="flex items-center gap-4 mb-4 border-b pb-2">
                <div class="bg-brand-blue text-white p-2 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 uppercase">Cadastrar Novo CIF</h2>
            </div>
            
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="criar">
                
                <!-- Linha 1: Dados Principais -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-6">
                        <label class="form-label block mb-1">CIF - Nome da Empresa *</label>
                        <input type="text" name="razao_social" required placeholder="Razão Social / Nome" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-brand-blue font-bold text-lg uppercase text-brand-blue">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">CPF</label>
                        <input type="text" name="cpf" id="cpf" placeholder="000.000.000-00" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">CNPJ *</label>
                        <input type="text" name="cnpj" id="cnpj" required placeholder="XX.XXX.XXX/XXXX-XX" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">Insc. Est.</label>
                        <input type="text" name="insc_estadual" placeholder="Isento" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <!-- Linha 2: Endereço -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-5">
                        <label class="form-label block mb-1">Endereço</label>
                        <input type="text" name="endereco" id="endereco" placeholder="Rua, Av..." class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-1">
                        <label class="form-label block mb-1">Nº</label>
                        <input type="text" name="numero" placeholder="Nº" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">Complemento</label>
                        <input type="text" name="complemento" placeholder="Compl." class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Telefone(s)</label>
                        <input type="text" name="telefone" id="telefone" placeholder="(11) 2000-0000" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <!-- Linha 3: Endereço cont. -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Bairro</label>
                        <input type="text" name="bairro" id="bairro" placeholder="Bairro" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Cidade</label>
                        <input type="text" name="cidade" id="cidade" placeholder="Cidade" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-1">
                        <label class="form-label block mb-1">UF</label>
                        <input type="text" name="uf" id="uf" maxlength="2" placeholder="UF" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">CEP</label>
                        <input type="text" name="cep" id="cep" placeholder="00000-000" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Celular</label>
                        <input type="text" name="celular" id="celular" placeholder="(11) 99999-9999" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <!-- Linha 4: Contatos -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Contato</label>
                        <input type="text" name="contato_nome" placeholder="Nome do contato" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Celular (Contato)</label>
                        <input type="text" name="contato_celular" id="contato_celular" placeholder="(11) 99999-9999" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Departamento</label>
                        <input type="text" name="contato_departamento" placeholder="Ex: Compras" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">Ramal</label>
                        <input type="text" name="contato_ramal" placeholder="Ramal" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <!-- Linha 5: E-mails e WhatsApp -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">E-mail</label>
                        <input type="email" name="email" placeholder="email@empresa.com.br" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold lowercase">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">DANFE para</label>
                        <input type="email" name="email_danfe" placeholder="danfe@empresa.com.br" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold lowercase">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1 text-green-600">WhatsApp</label>
                        <input type="text" name="whatsapp" id="whatsapp" placeholder="(11) 99999-9999" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <!-- Observação -->
                <div>
                    <label class="form-label block mb-1">Observação</label>
                    <textarea name="observacao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-brand-blue font-semibold"></textarea>
                </div>

                <div class="pt-4 border-t border-gray-200 mt-4 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modalNovoCif').classList.add('hidden')" class="bg-gray-200 text-gray-700 hover:bg-gray-300 font-bold py-2 px-4 rounded transition-colors text-sm">Cancelar</button>
                    <button type="submit" class="bg-green-600 text-white hover:bg-green-700 font-bold py-2 px-4 rounded shadow-md transition-all text-sm flex items-center gap-2 justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Salvar e fechar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: EDIÇÃO DE CIF                       -->
    <!-- ========================================== -->
    <?php if($cif_edicao): ?>
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl p-6 relative my-8">
            <a href="cifs.php" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 font-bold text-2xl leading-none">&times;</a>
            
            <div class="flex items-center justify-between mb-4 border-b pb-2">
                <div class="flex items-center gap-4">
                    <div class="bg-brand-blue text-white p-2 rounded-lg">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 uppercase">Editar CIF</h2>
                </div>
                <span class="text-xl text-gray-500 font-mono tracking-widest"><?= str_pad($cif_edicao['id'], 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            
            <form method="POST" class="space-y-3">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="id" value="<?= $cif_edicao['id'] ?>">
                
                <!-- Linha 1: Dados Principais -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-6">
                        <label class="form-label block mb-1">CIF - Nome da Empresa *</label>
                        <input type="text" name="razao_social" required value="<?= htmlspecialchars($cif_edicao['razao_social']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-brand-blue font-bold text-lg uppercase text-brand-blue">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">CPF</label>
                        <input type="text" name="cpf" id="cpf_edit" value="<?= htmlspecialchars($cif_edicao['cpf']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">CNPJ *</label>
                        <input type="text" name="cnpj" id="cnpj_edit" required value="<?= htmlspecialchars($cif_edicao['cnpj']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">Insc. Est.</label>
                        <input type="text" name="insc_estadual" value="<?= htmlspecialchars($cif_edicao['insc_estadual']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <!-- Linha 2: Endereço -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-5">
                        <label class="form-label block mb-1">Endereço</label>
                        <input type="text" name="endereco" id="endereco_edit" value="<?= htmlspecialchars($cif_edicao['endereco']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-1">
                        <label class="form-label block mb-1">Nº</label>
                        <input type="text" name="numero" value="<?= htmlspecialchars($cif_edicao['numero']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">Complemento</label>
                        <input type="text" name="complemento" value="<?= htmlspecialchars($cif_edicao['complemento']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Telefone(s)</label>
                        <input type="text" name="telefone" id="telefone_edit" value="<?= htmlspecialchars($cif_edicao['telefone']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <!-- Linha 3: Endereço cont. -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Bairro</label>
                        <input type="text" name="bairro" id="bairro_edit" value="<?= htmlspecialchars($cif_edicao['bairro']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Cidade</label>
                        <input type="text" name="cidade" id="cidade_edit" value="<?= htmlspecialchars($cif_edicao['cidade']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-1">
                        <label class="form-label block mb-1">UF</label>
                        <input type="text" name="uf" id="uf_edit" maxlength="2" value="<?= htmlspecialchars($cif_edicao['uf']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">CEP</label>
                        <input type="text" name="cep" id="cep_edit" value="<?= htmlspecialchars($cif_edicao['cep']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Celular</label>
                        <input type="text" name="celular" id="celular_edit" value="<?= htmlspecialchars($cif_edicao['celular']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <!-- Linha 4: Contatos -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Contato</label>
                        <input type="text" name="contato_nome" value="<?= htmlspecialchars($cif_edicao['contato_nome']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Celular (Contato)</label>
                        <input type="text" name="contato_celular" id="contato_celular_edit" value="<?= htmlspecialchars($cif_edicao['contato_celular']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Departamento</label>
                        <input type="text" name="contato_departamento" value="<?= htmlspecialchars($cif_edicao['contato_departamento']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">Ramal</label>
                        <input type="text" name="contato_ramal" value="<?= htmlspecialchars($cif_edicao['contato_ramal']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <!-- Linha 5: E-mails e WhatsApp -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">E-mail</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($cif_edicao['email']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold lowercase">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">DANFE para</label>
                        <input type="email" name="email_danfe" value="<?= htmlspecialchars($cif_edicao['email_danfe']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold lowercase">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1 text-green-600">WhatsApp</label>
                        <input type="text" name="whatsapp" id="whatsapp_edit" value="<?= htmlspecialchars($cif_edicao['whatsapp']) ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>

                <div>
                    <label class="form-label block mb-1">Observação</label>
                    <textarea name="observacao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-brand-blue font-semibold"><?= htmlspecialchars($cif_edicao['observacao']) ?></textarea>
                </div>

                <!-- Botões de Ação do Modal de Edição -->
                <div class="pt-4 border-t border-gray-200 mt-4 flex flex-col md:flex-row justify-between items-center gap-2">
                    
                    <a href="cifs.php?delete=<?= $cif_edicao['id'] ?>" onclick="return confirm('Tem certeza que deseja EXCLUIR este CIF?')" class="w-full md:w-auto bg-red-600 text-white hover:bg-red-700 font-bold py-2 px-6 rounded shadow-md transition-all text-sm text-center">
                        Excluir
                    </a>

                    <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                        <a href="cifs.php" class="bg-gray-200 text-gray-700 hover:bg-gray-300 font-bold py-2 px-6 rounded transition-colors text-sm text-center">
                            Cancelar
                        </a>
                        <button type="submit" class="bg-green-600 text-white hover:bg-green-700 font-bold py-2 px-6 rounded shadow-md transition-all text-sm flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Salvar e fechar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <!-- SCRIPT PARA MÁSCARAS E VIACEP -->
    <script>
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

            const phoneMaskOptions = { mask: [{ mask: '(00) 0000-0000' }, { mask: '(00) 00000-0000' }] };
            const cnpjMaskOptions = { mask: 'XX.XXX.XXX/XXXX-XX', definitions: { 'X': /[a-zA-Z0-9]/ } };

            // --- MÁSCARAS DO MODAL NOVO CIF ---
            if(document.getElementById('cep')) {
                IMask(document.getElementById('cep'), { mask: '00000-000' });
                document.getElementById('cep').addEventListener('blur', function() {
                    buscarCEP(this.value, { endereco: 'endereco', bairro: 'bairro', cidade: 'cidade', uf: 'uf' });
                });
            }
            if(document.getElementById('cpf')) IMask(document.getElementById('cpf'), { mask: '000.000.000-00' });
            if(document.getElementById('cnpj')) IMask(document.getElementById('cnpj'), cnpjMaskOptions);
            if(document.getElementById('telefone')) IMask(document.getElementById('telefone'), { mask: '(00) 0000-0000' });
            if(document.getElementById('celular')) IMask(document.getElementById('celular'), phoneMaskOptions);
            if(document.getElementById('whatsapp')) IMask(document.getElementById('whatsapp'), phoneMaskOptions);
            if(document.getElementById('contato_celular')) IMask(document.getElementById('contato_celular'), phoneMaskOptions);

            // --- MÁSCARAS DO MODAL EDIÇÃO ---
            if(document.getElementById('cep_edit')) {
                IMask(document.getElementById('cep_edit'), { mask: '00000-000' });
                document.getElementById('cep_edit').addEventListener('blur', function() {
                    buscarCEP(this.value, { endereco: 'endereco_edit', bairro: 'bairro_edit', cidade: 'cidade_edit', uf: 'uf_edit' });
                });
            }
            if(document.getElementById('cpf_edit')) IMask(document.getElementById('cpf_edit'), { mask: '000.000.000-00' });
            if(document.getElementById('cnpj_edit')) IMask(document.getElementById('cnpj_edit'), cnpjMaskOptions);
            if(document.getElementById('telefone_edit')) IMask(document.getElementById('telefone_edit'), { mask: '(00) 0000-0000' });
            if(document.getElementById('celular_edit')) IMask(document.getElementById('celular_edit'), phoneMaskOptions);
            if(document.getElementById('whatsapp_edit')) IMask(document.getElementById('whatsapp_edit'), phoneMaskOptions);
            if(document.getElementById('contato_celular_edit')) IMask(document.getElementById('contato_celular_edit'), phoneMaskOptions);

        });
    </script>
</body>
</html>