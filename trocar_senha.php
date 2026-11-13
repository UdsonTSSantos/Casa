<?php
// trocar_senha.php
session_start();
require_once __DIR__ . '/src/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Validação: Mínimo 8 caracteres, 1 letra, 1 número, 1 caractere especial
    $regex = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/';

    if ($nova_senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } elseif (!preg_match($regex, $nova_senha)) {
        $erro = "A senha deve ter no mínimo 8 caracteres, incluindo letra, número e caractere especial (@$!%*#?&).";
    } else {
        $pdo = Database::getInstance();
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, must_change_password = 0 WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['usuario_id']]);

        $sucesso = "Senha alterada com sucesso! Redirecionando...";
        header("Refresh: 2; URL=dashboard.php");
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - Casa Forte</title>
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
<body class="bg-bg-main min-h-screen flex items-center justify-center font-sans p-4">

    <div class="bg-white p-8 md:p-10 rounded-2xl shadow-xl w-full max-w-md border border-gray-200">
        <h1 class="text-2xl font-bold text-gray-800 mb-2 text-center">Primeiro Acesso</h1>
        <p class="text-sm text-gray-600 mb-6 text-center">Para sua segurança, crie uma nova senha forte.</p>

        <?php if($erro): ?>
            <div class="bg-red-50 text-red-700 p-3 rounded mb-4 text-sm font-semibold border-l-4 border-red-500"><?= $erro ?></div>
        <?php endif; ?>
        <?php if($sucesso): ?>
            <div class="bg-green-50 text-green-700 p-3 rounded mb-4 text-sm font-semibold border-l-4 border-green-500"><?= $sucesso ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="relative">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nova Senha</label>
                <input type="password" name="nova_senha" id="nova_senha" required placeholder="Mín. 8 carac., letras, números e símbolos" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue font-semibold pr-12">
                <!-- Botão do Olho -->
                <button type="button" onclick="togglePassword('nova_senha', 'icon_nova')" class="absolute right-3 top-10 text-gray-500 hover:text-brand-blue">
                    <svg id="icon_nova" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            
            <div class="relative">
                <label class="block text-gray-700 text-sm font-bold mb-2">Confirmar Nova Senha</label>
                <input type="password" name="confirmar_senha" id="confirmar_senha" required placeholder="Digite novamente" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue font-semibold pr-12">
                <!-- Botão do Olho -->
                <button type="button" onclick="togglePassword('confirmar_senha', 'icon_conf')" class="absolute right-3 top-10 text-gray-500 hover:text-brand-blue">
                    <svg id="icon_conf" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            
            <button type="submit" class="w-full bg-brand-blue text-white font-bold py-3 px-4 rounded-lg shadow-md transition-all duration-200 hover:bg-blue-900 hover:-translate-y-0.5 active:scale-95 mt-4">
                Salvar Nova Senha
            </button>
        </form>
    </div>

</body>
</html>