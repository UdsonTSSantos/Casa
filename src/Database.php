<?php
// src/Database.php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dbPath = __DIR__ . '/../storage/casaforte.db';
        $this->pdo = new PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        $this->pdo->exec('PRAGMA journal_mode = WAL;');
        $this->pdo->exec('PRAGMA busy_timeout = 5000;');

        $this->initDatabase();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }

    private function initDatabase() {
        // 1. Usuários
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                senha TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                must_change_password INTEGER DEFAULT 0,
                ativo INTEGER DEFAULT 1, 
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // 2. Clientes
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS clientes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                razao_social TEXT NOT NULL,
                cpf TEXT,
                cnpj TEXT,
                insc_estadual TEXT,
                endereco TEXT,
                numero TEXT,
                complemento TEXT,
                bairro TEXT,
                cidade TEXT,
                uf TEXT,
                cep TEXT,
                contato_nome TEXT,
                contato_celular TEXT,
                contato_departamento TEXT,
                telefone TEXT,
                celular TEXT,
                whatsapp TEXT,
                email TEXT,
                email_danfe TEXT,
                is_fornecedor INTEGER DEFAULT 0,
                cobranca_endereco TEXT,
                cobranca_numero TEXT,
                cobranca_complemento TEXT,
                cobranca_bairro TEXT,
                cobranca_cidade TEXT,
                cobranca_uf TEXT,
                cobranca_cep TEXT,
                entrega_endereco TEXT,
                entrega_numero TEXT,
                entrega_complemento TEXT,
                entrega_bairro TEXT,
                entrega_cidade TEXT,
                entrega_uf TEXT,
                entrega_cep TEXT,
                observacao TEXT,
                material_comercializado TEXT,
                ativo INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // 3. Materiais
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS materiais (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo TEXT UNIQUE NOT NULL,
                descricao TEXT NOT NULL,
                unidade TEXT NOT NULL,
                preco_unitario REAL DEFAULT 0.00,
                ipi REAL DEFAULT 0.00,
                icms REAL DEFAULT 0.00,
                ativo INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // 4. Fornecedores
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fornecedores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                razao_social TEXT NOT NULL,
                cep TEXT,
                endereco TEXT,
                numero TEXT,
                bairro TEXT,
                cidade TEXT,
                uf TEXT,
                cnpj TEXT,
                ie TEXT,
                telefone TEXT,
                contato_nome TEXT,
                celular TEXT,
                email TEXT,
                whatsapp TEXT,
                email_danfe TEXT,
                observacao TEXT,
                ativo INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // 5. CIF (NOVA - Com CNPJ Único)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cifs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                razao_social TEXT NOT NULL,
                cpf TEXT,
                cnpj TEXT UNIQUE,
                insc_estadual TEXT,
                endereco TEXT,
                numero TEXT,
                complemento TEXT,
                bairro TEXT,
                cidade TEXT,
                uf TEXT,
                cep TEXT,
                telefone TEXT,
                celular TEXT,
                whatsapp TEXT,
                contato_nome TEXT,
                contato_celular TEXT,
                contato_departamento TEXT,
                contato_ramal TEXT,
                email TEXT,
                email_danfe TEXT,
                observacao TEXT,
                ativo INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Super Admin
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE email = 'suporte@ast7.com.br'");
        if ($stmt->fetchColumn() == 0) {
            $nome = 'Suporte AST7';
            $email = 'suporte@ast7.com.br';
            $senhaHash = password_hash('2684a38c31', PASSWORD_DEFAULT);
            $insert = $this->pdo->prepare("INSERT INTO usuarios (nome, email, senha, role, ativo) VALUES (?, ?, ?, 'superadmin', 1)");
            $insert->execute([$nome, $email, $senhaHash]);
        }
    }
}
?>