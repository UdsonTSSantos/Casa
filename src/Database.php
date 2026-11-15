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
        // ... (Tabelas anteriores: usuarios, clientes, materiais, fornecedores, cifs, transportadoras, empresa) ...
        // Para não quebrar o arquivo, coloquei as tabelas anteriores em comentário. 
        // MANTENHA AS SUAS TABELAS ANTERIORES AQUI E SUBSTITUA APENAS A TABELA DE PEDIDOS_ITENS ABAIXO.

        // 8. Pedidos (Cabeçalho) - Mantenha como estava
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS pedidos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                numero_pedido INTEGER,
                data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
                vendedor_id INTEGER,
                cliente_id INTEGER,
                cliente_att TEXT,
                fornecedor_id INTEGER,
                cif_id INTEGER,
                endereco_cobranca TEXT,
                endereco_entrega TEXT,
                faturamento TEXT,
                condicao_avista TEXT,
                condicao_faturado TEXT,
                is_fob_fornecedor INTEGER DEFAULT 0,
                fob_fornecedor_texto TEXT,
                observacao TEXT,
                total_sem_imposto REAL DEFAULT 0.00,
                total_com_imposto REAL DEFAULT 0.00,
                total_ipi REAL DEFAULT 0.00,
                total_icms REAL DEFAULT 0.00,
                qtd_itens REAL DEFAULT 0.00,
                status TEXT DEFAULT 'orcamento',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        try { $this->pdo->query("SELECT fob_fornecedor_texto FROM pedidos LIMIT 1"); } 
        catch (PDOException $e) { $this->pdo->exec("ALTER TABLE pedidos ADD COLUMN fob_fornecedor_texto TEXT"); }

        // 9. Pedidos Itens (Linhas) - ATUALIZADO COM descricao_manual
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS pedidos_itens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pedido_id INTEGER,
                material_id INTEGER,
                descricao_manual TEXT,
                quantidade REAL,
                preco_unitario REAL,
                ipi REAL,
                icms REAL,
                subtotal_sem_imposto REAL,
                subtotal_com_imposto REAL,
                observacao_item TEXT,
                FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
            )
        ");

        // Verifica se a coluna existe em bancos antigos
        try { $this->pdo->query("SELECT descricao_manual FROM pedidos_itens LIMIT 1"); } 
        catch (PDOException $e) { $this->pdo->exec("ALTER TABLE pedidos_itens ADD COLUMN descricao_manual TEXT"); }

        // Super Admin e Empresa (mantenha como estavam)
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