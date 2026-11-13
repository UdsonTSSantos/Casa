<?php
// usuarios.php
session_start();
require_once __DIR__ . '/src/Database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_role'] !== 'superadmin') {
    header("Location: dashboard.php");
    exit;
}

$pdo = Database::getInstance();
$senha_provisoria = null;
$usuario_edicao = null;

// --- PROCESSAMENTO DE AÇÕES ---

// 1. Criar Usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar') {
    $nome = trim($_POST['nome']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    if ($nome && $email) {
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $senha_provisoria = substr(str_shuffle($caracteres), 0, 8);
        $senhaHash = password_hash($senha_provisoria, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, role, must_change_password, ativo) VALUES (?, ?, ?, 'user', 1, 1)");
            $stmt->execute([$nome, $email, $senhaHash]);
            $_SESSION['sucesso'] = "Usuário criado com sucesso!";
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro: E-mail já cadastrado.";
        }
    }
}

// 2. Alternar Ativo/Inativo
if (isset($_GET['toggle'])) {
    $id_toggle = (int)$_GET['toggle'];
    if ($id_toggle !== $_SESSION['usuario_id']) {
        $pdo->exec("UPDATE usuarios SET ativo = CASE WHEN ativo = 1 THEN 0 ELSE 1 END WHERE id = $id_toggle");
        $_SESSION['sucesso'] = "Status do usuário alterado.";
    } else {
        $_SESSION['erro'] = "Você não pode desativar seu próprio usuário.";
    }
    header("Location: usuarios.php");
    exit;
}

// 3. Editar Usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $id_edit = (int)$_POST['id'];
    $nome_edit = trim($_POST['nome_edit']);
    $email_edit = filter_var($_POST['email_edit'], FILTER_VALIDATE_EMAIL);
    
    if ($nome_edit && $email_edit) {
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
            $stmt->execute([$nome_edit, $email_edit, $id_edit]);
            $_SESSION['sucesso'] = "Usuário atualizado com sucesso!";
            header("Location: usuarios.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro: E-mail já está em uso por outro usuário.";
        }
    }
}

// 4. Resetar Senha (Gerar provisória para usuário existente)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resetar') {
    $id_reset = (int)$_POST['id_reset'];
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $senha_provisoria = substr(str_shuffle($caracteres), 0, 8);
    $senhaHash = password_hash($senha_provisoria, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, must_change_password = 1 WHERE id = ?");
    $stmt->execute([$senhaHash, $id_reset]);
    $_SESSION['sucesso'] = "Senha resetada! A nova senha provisória é: <strong class='text-xl font-mono bg-yellow-200 px-2 py-1 rounded'>$senha_provisoria</strong>";
    header("Location: usuarios.php");
    exit;
}

// Captura dados para o modal de edição (se clicou em editar)
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id_edit]);
    $usuario_edicao = $stmt->fetch();
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY ativo DESC, id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - Casa Forte</title>
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
        // Função para mostrar/ocultar senha
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === "password") {
                input.type = "text";
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
            } else {
                input.type = "password";
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
            }
        }
    </script>
</head>
<body class="bg-bg-main min-h-screen flex flex-col font-sans text-text-main text-base">

    <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
        <img src="assets/ast7_logo.png" alt="AST7" class="h-10">
        <a href="dashboard.php" class="text-brand-blue hover:underline font-bold text-sm">Voltar ao Menu</a>
    </header>

    <main class="flex-grow p-6 md:p-10 max-w-6xl mx-auto w-full">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Gestão de Usuários</h1>

        <!-- Alertas -->
        <?php if(isset($_SESSION['erro'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 font-semibold text-sm"><?= $_SESSION['erro']; unset($_SESSION['erro']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['sucesso'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 font-semibold text-sm"><?= $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?></div>
        <?php endif; ?>

        <!-- FORMULÁRIO NO TOPO -->
        <div class="bg-white p-6 rounded-xl shadow-md mb-8 border border-gray-200">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Cadastrar Novo Usuário</h2>
            <form method="POST" class="flex flex-col md:flex-row gap-4 items-end">
                <input type="hidden" name="action" value="criar">
                <div class="flex-1 w-full">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nome Completo</label>
                    <input type="text" name="nome" required placeholder="Nome do funcionário" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue font-semibold">
                </div>
                <div class="flex-1 w-full">
                    <label class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                    <input type="email" name="email" required placeholder="email@empresa.com" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue font-semibold">
                </div>
                <button type="submit" class="w-full md:w-auto bg-brand-blue text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all duration-200 hover:bg-blue-900 hover:-translate-y-0.5 active:scale-95">
                    Criar Usuário
                </button>
            </form>
        </div>

        <!-- LISTAGEM EMBAIXO (Fonte Menor) -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 overflow-x-auto">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Usuários Cadastrados</h2>
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-200 text-gray-600 bg-gray-50">
                        <th class="py-3 px-3 font-bold">Nome</th>
                        <th class="py-3 px-3 font-bold">E-mail</th>
                        <th class="py-3 px-3 font-bold">Perfil</th>
                        <th class="py-3 px-3 font-bold">Status</th>
                        <th class="py-3 px-3 font-bold text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $u): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50 <?= $u['ativo'] == 0 ? 'opacity-60 bg-gray-50' : '' ?>">
                        <td class="py-3 px-3 font-semibold"><?= htmlspecialchars($u['nome']) ?></td>
                        <td class="py-3 px-3 text-gray-600"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-1 rounded text-xs font-bold <?= $u['role'] === 'superadmin' ? 'bg-orange-100 text-brand-orange' : 'bg-blue-100 text-brand-blue' ?>">
                                <?= strtoupper($u['role']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            <?php if($u['ativo']): ?>
                                <span class="text-green-600 font-bold">Ativo</span>
                            <?php else: ?>
                                <span class="text-red-500 font-bold">Inativo</span>
                            <?php endif; ?>
                            <?php if($u['must_change_password']): ?>
                                <br><span class="text-xs text-yellow-600 font-semibold">(Senha Provisória)</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-3">
                            <!-- Container Flex para alinhar os botões perfeitamente -->
                            <div class="flex items-center justify-center gap-2">
                                <a href="usuarios.php?edit=<?= $u['id'] ?>" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-3 py-1.5 rounded transition-colors text-xs font-medium" title="Editar">Editar</a>
                                
                                <?php if($u['id'] !== $_SESSION['usuario_id']): ?>
                                    <a href="usuarios.php?toggle=<?= $u['id'] ?>" class="<?= $u['ativo'] ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' ?> px-3 py-1.5 rounded transition-colors text-xs font-medium" title="<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>">
                                        <?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- MODAL DE EDIÇÃO -->
    <?php if($usuario_edicao): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 relative">
            
            <!-- Botão Fechar (X no topo) -->
            <a href="usuarios.php" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 font-bold text-2xl leading-none">&times;</a>
            
            <h2 class="text-xl font-bold text-gray-800 mb-4">Editar Usuário</h2>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="id" value="<?= $usuario_edicao['id'] ?>">
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nome Completo</label>
                    <input type="text" name="nome_edit" required value="<?= htmlspecialchars($usuario_edicao['nome']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue font-semibold">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                    <input type="email" name="email_edit" required value="<?= htmlspecialchars($usuario_edicao['email']) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue font-semibold">
                </div>

                <!-- Rodapé do Modal com Botão Fechar -->
                <div class="pt-4 border-t border-gray-200 mt-6 flex justify-end gap-3">
                    <a href="usuarios.php" class="bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold py-2 px-6 rounded-lg transition-colors text-sm flex items-center">
                        Fechar
                    </a>
                    <button type="submit" class="bg-brand-blue text-white font-bold py-2 px-6 rounded-lg shadow-md transition-all hover:bg-blue-900 active:scale-95 text-sm">
                        Salvar Alterações
                    </button>
                </div>
            </form>

            <!-- Formulário separado para Resetar Senha -->
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="resetar">
                <input type="hidden" name="id_reset" value="<?= $usuario_edicao['id'] ?>">
                <button type="submit" onclick="return confirm('Deseja gerar uma nova senha provisória para este usuário?')" class="w-full bg-orange-100 text-brand-orange hover:bg-orange-200 font-bold py-2 rounded-lg transition-colors text-sm border border-orange-300">
                    Resetar Senha (Gerar Provisória)
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'footer.php'; ?>
</body>
</html>