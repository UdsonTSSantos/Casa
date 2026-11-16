<?php
// esqueci_senha.php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="AST7 Softwares - Udson">
    <title>Recuperar Senha - Casa Forte</title>
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
    <style>
        input:not(:placeholder-shown) { background-color: #F3F4F6 !important; }
        input:focus { background-color: #FFFFFF !important; }
    </style>
</head>
<body class="bg-bg-main min-h-screen flex items-center justify-center font-sans p-4">

    <div class="bg-white p-8 md:p-10 rounded-2xl shadow-xl w-full max-w-md border border-gray-200">
        <div class="flex justify-center mb-6">
            <img src="assets/ast7_logo.png" alt="AST7" class="h-16">
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2 text-center">Recuperar Senha</h1>
        <p class="text-sm text-gray-600 mb-6 text-center">Informe o e-mail cadastrado para receber o link de redefinição.</p>

        <?php if(isset($_SESSION['erro'])): ?>
            <div class="bg-red-50 text-red-700 p-3 rounded mb-4 text-sm font-semibold border-l-4 border-red-500">
                <?= $_SESSION['erro']; unset($_SESSION['erro']); ?>
            </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['sucesso'])): ?>
            <div class="bg-green-50 text-green-700 p-3 rounded mb-4 text-sm font-semibold border-l-4 border-green-500">
                <?= $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
            </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['link_teste'])): ?>
            <!-- APENAS PARA TESTES LOCAIS (Já que a Locaweb bloqueia o mail()) -->
            <div class="bg-yellow-50 text-yellow-800 p-4 rounded mb-4 text-sm font-semibold border-l-4 border-yellow-500 break-all">
                <p class="mb-2">⚠️ Simulação de E-mail (Ambiente Local):</p>
                <a href="<?= $_SESSION['link_teste'] ?>" class="text-blue-600 underline">Clique aqui para redefinir sua senha</a>
            </div>
            <?php unset($_SESSION['link_teste']); ?>
        <?php endif; ?>

        <form action="processar_recuperacao.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">E-mail Cadastrado</label>
                <input type="email" name="email" required placeholder="seu@email.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue font-semibold">
            </div>
            
            <button type="submit" class="w-full bg-brand-blue text-white font-bold py-3 px-4 rounded-lg shadow-md transition-all duration-200 hover:bg-blue-900 active:scale-95">
                Enviar Link de Recuperação
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="index.php" class="text-sm font-semibold text-gray-600 hover:text-brand-blue hover:underline">Voltar para o Login</a>
        </div>
    </div>

</body>
</html>