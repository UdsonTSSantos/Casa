<?php
// empresa.php
session_start();
require_once __DIR__ . '/src/Database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$pdo = Database::getInstance();
$mensagem_erro = '';
$mensagem_sucesso = '';

// Busca dados da empresa
$empresa = $pdo->query("SELECT * FROM empresa WHERE id = 1")->fetch();

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $logo_path = $empresa['logo']; // Mantém a logo atual por padrão

        // Verifica se um novo arquivo foi enviado
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/assets/uploads/';
            
            // Cria a pasta se não existir
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileTmpPath = $_FILES['logo']['tmp_name'];
            $fileName = $_FILES['logo']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Extensões permitidas
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                // Gera um nome único para evitar cache do navegador
                $newFileName = 'logo_empresa_' . time() . '.' . $fileExtension;
                $dest_path = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Apaga a logo antiga se existir
                    if (!empty($empresa['logo']) && file_exists(__DIR__ . '/' . $empresa['logo'])) {
                        unlink(__DIR__ . '/' . $empresa['logo']);
                    }
                    $logo_path = 'assets/uploads/' . $newFileName;
                } else {
                    throw new Exception("Erro ao mover o arquivo de imagem.");
                }
            } else {
                throw new Exception("Formato de imagem inválido. Use JPG, PNG, WEBP ou SVG.");
            }
        }

        // Atualiza os dados no banco
        $stmt = $pdo->prepare("
            UPDATE empresa SET 
                cnpj=?, razao_social=?, nome_fantasia=?, cep=?, endereco=?, numero=?, complemento=?, bairro=?, cidade=?, uf=?,
                telefone1=?, telefone2=?, telefone3=?, celular1=?, celular2=?, email=?, site=?, logo=?
            WHERE id = 1
        ");
        $stmt->execute([
            trim($_POST['cnpj']), trim($_POST['razao_social']), trim($_POST['nome_fantasia']),
            trim($_POST['cep']), trim($_POST['endereco']), trim($_POST['numero']), trim($_POST['complemento']),
            trim($_POST['bairro']), trim($_POST['cidade']), trim($_POST['uf']),
            trim($_POST['telefone1']), trim($_POST['telefone2']), trim($_POST['telefone3']),
            trim($_POST['celular1']), trim($_POST['celular2']),
            trim($_POST['email']), trim($_POST['site']), $logo_path
        ]);

        $mensagem_sucesso = "Dados da empresa atualizados com sucesso!";
        
        // Atualiza a variável local para refletir os novos dados
        $empresa = $pdo->query("SELECT * FROM empresa WHERE id = 1")->fetch();

    } catch (Exception $e) {
        $mensagem_erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="AST7 Softwares - Udson">
    <title>Cadastro da Empresa - Casa Forte</title>
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

    <main class="flex-grow p-4 md:p-6 max-w-5xl mx-auto w-full">
        
        <div class="bg-white p-6 md:p-8 rounded-xl shadow-md border border-gray-200">
            <div class="flex items-center gap-4 mb-6 border-b pb-4">
                <div class="bg-brand-blue text-white p-3 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 uppercase">Dados da Empresa</h1>
                    <p class="text-sm text-gray-500">Essas informações serão usadas nos cabeçalhos dos relatórios.</p>
                </div>
            </div>

            <?php if($mensagem_erro): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 font-semibold text-sm"><?= $mensagem_erro ?></div>
            <?php endif; ?>
            <?php if($mensagem_sucesso): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 font-semibold text-sm"><?= $mensagem_sucesso ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                
                <!-- Logo e Dados Principais -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                    
                    <!-- Upload de Logo -->
                    <div class="md:col-span-3 flex flex-col items-center bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <label class="form-label block mb-3 text-center w-full">Logotipo da Empresa</label>
                        
                        <div class="w-32 h-32 bg-white rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden mb-3">
                            <?php if(!empty($empresa['logo']) && file_exists(__DIR__ . '/' . $empresa['logo'])): ?>
                                <img src="<?= htmlspecialchars($empresa['logo']) ?>?v=<?= time() ?>" alt="Logo" class="max-w-full max-h-full object-contain">
                            <?php else: ?>
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <?php endif; ?>
                        </div>
                        
                        <input type="file" name="logo" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-brand-blue file:text-white hover:file:bg-blue-900 cursor-pointer transition-colors">
                        <span class="text-[10px] text-gray-400 mt-2 text-center">Formatos: JPG, PNG, WEBP.</span>
                    </div>

                    <!-- Dados da Empresa -->
                    <div class="md:col-span-9 space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-4">
                                <label class="form-label block mb-1 text-brand-blue">CNPJ</label>
                                <input type="text" name="cnpj" id="cnpj" value="<?= htmlspecialchars($empresa['cnpj'] ?? '') ?>" placeholder="Digite o CNPJ" class="w-full px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-brand-blue font-semibold transition-colors duration-300">
                            </div>
                            <div class="md:col-span-8">
                                <label class="form-label block mb-1">Razão Social *</label>
                                <input type="text" name="razao_social" id="razao_social" required value="<?= htmlspecialchars($empresa['razao_social'] ?? '') ?>" placeholder="Razão Social" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                            </div>
                            <div class="md:col-span-8">
                                <label class="form-label block mb-1">Nome Fantasia</label>
                                <input type="text" name="nome_fantasia" id="nome_fantasia" value="<?= htmlspecialchars($empresa['nome_fantasia'] ?? '') ?>" placeholder="Nome Fantasia" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                            </div>
                            <div class="md:col-span-4">
                                <label class="form-label block mb-1">Site</label>
                                <input type="text" name="site" value="<?= htmlspecialchars($empresa['site'] ?? '') ?>" placeholder="www.empresa.com.br" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold lowercase">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 border-t pt-4">
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">CEP</label>
                        <input type="text" name="cep" id="cep" value="<?= htmlspecialchars($empresa['cep'] ?? '') ?>" placeholder="00000-000" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-6">
                        <label class="form-label block mb-1">Endereço</label>
                        <input type="text" name="endereco" id="endereco" value="<?= htmlspecialchars($empresa['endereco'] ?? '') ?>" placeholder="Rua, Av..." class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-1">
                        <label class="form-label block mb-1">Nº</label>
                        <input type="text" name="numero" id="numero" value="<?= htmlspecialchars($empresa['numero'] ?? '') ?>" placeholder="Nº" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-3">
                        <label class="form-label block mb-1">Complemento</label>
                        <input type="text" name="complemento" id="complemento" value="<?= htmlspecialchars($empresa['complemento'] ?? '') ?>" placeholder="Sala, Andar..." class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Bairro</label>
                        <input type="text" name="bairro" id="bairro" value="<?= htmlspecialchars($empresa['bairro'] ?? '') ?>" placeholder="Bairro" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-6">
                        <label class="form-label block mb-1">Cidade</label>
                        <input type="text" name="cidade" id="cidade" value="<?= htmlspecialchars($empresa['cidade'] ?? '') ?>" placeholder="Cidade" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label block mb-1">UF</label>
                        <input type="text" name="uf" id="uf" maxlength="2" value="<?= htmlspecialchars($empresa['uf'] ?? '') ?>" placeholder="UF" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold uppercase">
                    </div>
                </div>

                <!-- Contatos -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 border-t pt-4">
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Telefone Fixo 1</label>
                        <input type="text" name="telefone1" id="telefone1" value="<?= htmlspecialchars($empresa['telefone1'] ?? '') ?>" placeholder="(11) 2000-0000" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Telefone Fixo 2</label>
                        <input type="text" name="telefone2" id="telefone2" value="<?= htmlspecialchars($empresa['telefone2'] ?? '') ?>" placeholder="(11) 2000-0000" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">Telefone Fixo 3</label>
                        <input type="text" name="telefone3" id="telefone3" value="<?= htmlspecialchars($empresa['telefone3'] ?? '') ?>" placeholder="(11) 2000-0000" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1 text-brand-blue">Celular 1</label>
                        <input type="text" name="celular1" id="celular1" value="<?= htmlspecialchars($empresa['celular1'] ?? '') ?>" placeholder="(11) 99999-9999" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1 text-brand-blue">Celular 2</label>
                        <input type="text" name="celular2" id="celular2" value="<?= htmlspecialchars($empresa['celular2'] ?? '') ?>" placeholder="(11) 99999-9999" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold">
                    </div>
                    <div class="md:col-span-4">
                        <label class="form-label block mb-1">E-mail</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>" placeholder="contato@empresa.com.br" class="w-full px-3 py-1.5 border border-gray-300 rounded font-semibold lowercase">
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="bg-brand-blue text-white font-bold py-3 px-8 rounded-lg shadow-md transition-all duration-200 hover:bg-blue-900 hover:-translate-y-0.5 active:scale-95 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <!-- SCRIPT PARA MÁSCARAS, VIACEP E API CNPJ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- FUNÇÃO VIACEP ---
            async function buscarCEP(cepValue) {
                const cleanCep = cepValue.replace(/\D/g, '');
                if (cleanCep.length !== 8) return;
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
                    const data = await response.json();
                    if (!data.erro) {
                        document.getElementById('endereco').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('uf').value = data.uf || '';
                    }
                } catch (error) { console.error("Erro CEP:", error); }
            }

            // --- FUNÇÃO BRASILAPI (CNPJ) ---
            async function buscarCNPJ(cnpjInput) {
                const cnpjValue = cnpjInput.value.replace(/\D/g, '');
                if (cnpjValue.length !== 14) return;

                cnpjInput.classList.add('api-loading');
                
                try {
                    const response = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${cnpjValue}`);
                    if (!response.ok) throw new Error('CNPJ não encontrado');
                    const data = await response.json();

                    const setVal = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) el.value = val || '';
                    };

                    setVal('razao_social', data.razao_social);
                    setVal('nome_fantasia', data.nome_fantasia);
                    setVal('cep', data.cep);
                    setVal('endereco', data.logradouro);
                    setVal('numero', data.numero);
                    setVal('complemento', data.complemento);
                    setVal('bairro', data.bairro);
                    setVal('cidade', data.municipio);
                    setVal('uf', data.uf);
                    setVal('telefone1', data.ddd_telefone_1);
                    setVal('email', data.email);

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

            // --- APLICANDO MÁSCARAS ---
            if(document.getElementById('cnpj')) {
                IMask(document.getElementById('cnpj'), cnpjMaskOptions);
                document.getElementById('cnpj').addEventListener('blur', function() { buscarCNPJ(this); });
            }
            if(document.getElementById('cep')) {
                IMask(document.getElementById('cep'), { mask: '00000-000' });
                document.getElementById('cep').addEventListener('blur', function() { buscarCEP(this.value); });
            }

            // Máscaras dos Telefones Fixos (8 dígitos)
            const telMaskOptions = { mask: '(00) 0000-0000' };
            if(document.getElementById('telefone1')) IMask(document.getElementById('telefone1'), telMaskOptions);
            if(document.getElementById('telefone2')) IMask(document.getElementById('telefone2'), telMaskOptions);
            if(document.getElementById('telefone3')) IMask(document.getElementById('telefone3'), telMaskOptions);

            // Máscaras dos Celulares (9 dígitos)
            if(document.getElementById('celular1')) IMask(document.getElementById('celular1'), phoneMaskOptions);
            if(document.getElementById('celular2')) IMask(document.getElementById('celular2'), phoneMaskOptions);

        });
    </script>
</body>
</html>