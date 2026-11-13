<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php"); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Casa Forte</title>
    <link rel="icon" type="image/png" href="assets/ast7_logo.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'brand-blue': '#003B73',
                        'brand-orange': '#F97316',
                        'bg-main': '#F3F4F6',
                        'text-main': '#1E293B',
                    }
                }
            }
        }

        // Função para mostrar/ocultar senha
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === "password") {
                input.type = "text";
                // Ícone de olho cortado (esconder)
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
            } else {
                input.type = "password";
                // Ícone de olho aberto (mostrar)
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
            }
        }
    </script>
    <style>
        /* Regra: Fundo cinza claro apenas quando o campo estiver preenchido */
        input:not(:placeholder-shown) {
            background-color: #F3F4F6 !important; 
        }
        input:focus {
            background-color: #FFFFFF !important;
        }
    </style>
</head>
<body class="bg-white min-h-screen flex flex-col font-sans text-text-main text-base sm:text-lg">

    <!-- Container Principal em formato de linha (split screen) -->
    <main class="flex-grow flex flex-col md:flex-row w-full">
        
        <!-- LADO ESQUERDO: FORMULÁRIO -->
        <div class="w-full md:w-1/2 flex items-center justify-center p-8 sm:p-12 lg:p-24 bg-white">
            <div class="w-full max-w-md">
                
                <h1 class="text-4xl sm:text-5xl font-extrabold text-black mb-10 tracking-tight">Login</h1>

                <?php if(isset($_SESSION['erro'])): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 text-sm sm:text-base font-semibold" role="alert">
                        <?= htmlspecialchars($_SESSION['erro']); unset($_SESSION['erro']); ?>
                    </div>
                <?php endif; ?>
                <?php if(isset($_SESSION['sucesso'])): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 text-sm sm:text-base font-semibold" role="alert">
                        <?= htmlspecialchars($_SESSION['sucesso']); unset($_SESSION['sucesso']); ?>
                    </div>
                <?php endif; ?>

                <form action="auth.php" method="POST" class="space-y-5">
                    
                    <div>
                        <label class="block text-gray-800 text-sm sm:text-base font-bold mb-2">Informe o seu e-mail:</label>
                        <input type="email" name="email" required placeholder="seu@email.com" 
                               class="w-full px-4 py-3 border border-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue focus:border-transparent text-gray-900 font-semibold text-base sm:text-lg transition-colors duration-200">
                    </div>

                    <div class="relative">
                        <label class="block text-gray-800 text-sm sm:text-base font-bold mb-2">Informe a sua senha:</label>
                        <input type="password" name="senha" id="senha_login" required placeholder="Sua senha" 
                               class="w-full px-4 py-3 border border-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-blue focus:border-transparent text-gray-900 font-semibold text-base sm:text-lg transition-colors duration-200 pr-12">
                        
                        <!-- Botão do Olho -->
                        <button type="button" onclick="togglePassword('senha_login', 'icon_senha_login')" class="absolute right-3 top-11 text-gray-500 hover:text-brand-blue">
                            <svg id="icon_senha_login" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Botão com gradiente e efeito 3D -->
                    <button type="submit" 
                            class="w-full bg-gradient-to-b from-[#0055A4] to-[#003B73] text-white font-bold text-lg sm:text-xl py-3 px-4 rounded-lg mt-6 shadow-md border-b-4 border-[#002244]
                                   transition-all duration-200 ease-in-out 
                                   hover:from-[#0066c2] hover:to-[#004a91] hover:-translate-y-0.5 hover:shadow-lg
                                   active:translate-y-0 active:border-b-0 active:mb-1 active:shadow-inner">
                        LOGIN
                    </button>
                </form>

                <div class="mt-6">
                    <a href="#" class="text-sm sm:text-base font-semibold text-gray-600 hover:text-brand-blue transition-colors duration-200">
                        Esqueceu sua senha? <span class="text-[#0055A4] font-bold">Clique aqui.</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- LADO DIREITO: ILUSTRAÇÃO -->
        <div class="hidden md:flex md:w-1/2 bg-white items-center justify-center p-12 relative overflow-hidden">
            <!-- Imagem sem classes de sombra -->
            <img src="assets/img_login.webp" alt="Ilustração de Login" class="max-w-full max-h-[80vh] h-auto object-contain z-10">
        </div>

    </main>

    <!-- Inclusão do rodapé -->
    <?php include 'footer.php'; ?>

</body>
</html>