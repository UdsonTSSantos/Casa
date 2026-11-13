<?php
// dashboard.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="AST7 Softwares - Udson">
    <title>Dashboard - Casa Forte</title>
    <link rel="icon" type="image/png" href="assets/ast7_logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        'brand-blue': '#003B73',
                        'brand-orange': '#F97316',
                        'bg-main': '#F3F4F6',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-bg-main min-h-screen flex flex-col font-sans text-text-main text-base">

    <!-- Topbar -->
    <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
        <img src="assets/ast7_logo.png" alt="AST7" class="h-10">
        <div class="flex items-center gap-4">
            <span class="text-sm font-semibold text-gray-700">Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
            <?php if($_SESSION['usuario_role'] === 'superadmin'): ?>
                <span class="bg-brand-orange text-white text-xs font-bold px-2 py-1 rounded">SUPER ADMIN</span>
            <?php endif; ?>
            <a href="logout.php" class="text-red-600 hover:text-red-800 font-bold text-sm transition-colors">Sair</a>
        </div>
    </header>

    <main class="flex-grow p-6 md:p-10 max-w-7xl mx-auto w-full">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Menu Principal</h1>

        <!-- Grid de Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            
            <!-- Card: Novo Orçamento -->
            <a href="#" class="bg-[#334155] hover:bg-[#475569] text-white p-6 rounded-lg shadow-md transition-all duration-200 hover:-translate-y-1 flex items-center gap-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <div><h3 class="font-bold text-lg">Novo Orçamento</h3></div>
            </a>

            <!-- Card: Clientes -->
            <a href="clientes.php" class="bg-[#334155] hover:bg-[#475569] text-white p-6 rounded-lg shadow-md transition-all duration-200 hover:-translate-y-1 flex items-center gap-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <div><h3 class="font-bold text-lg">Clientes</h3></div>
            </a>

            <!-- Card: Materiais -->
            <a href="materiais.php" class="bg-[#334155] hover:bg-[#475569] text-white p-6 rounded-lg shadow-md transition-all duration-200 hover:-translate-y-1 flex items-center gap-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <div><h3 class="font-bold text-lg">Materiais</h3></div>
            </a>

            <!-- Card: Fornecedores -->
            <a href="fornecedores.php" class="bg-[#334155] hover:bg-[#475569] text-white p-6 rounded-lg shadow-md transition-all duration-200 hover:-translate-y-1 flex items-center gap-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <div><h3 class="font-bold text-lg">Fornecedores</h3></div>
            </a>

            <!-- Card: CIF (NOVO) -->
            <a href="cifs.php" class="bg-[#334155] hover:bg-[#475569] text-white p-6 rounded-lg shadow-md transition-all duration-200 hover:-translate-y-1 flex items-center gap-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div><h3 class="font-bold text-lg">CIF</h3></div>
            </a>

            <!-- Card: Usuários (Apenas Super Admin vê) -->
            <?php if($_SESSION['usuario_role'] === 'superadmin'): ?>
                <a href="usuarios.php" class="bg-[#334155] hover:bg-[#475569] text-white p-6 rounded-lg shadow-md transition-all duration-200 hover:-translate-y-1 flex items-center gap-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <div><h3 class="font-bold text-lg">Usuários</h3></div>
                </a>
            <?php endif; ?>

        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>