<?php
/**
 * CacheConnection.php
 * 
 * Camada de abstração para o InterSystems Caché.
 * 
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  COMO FUNCIONA EM PRODUÇÃO (servidor com Caché instalado)       │
 * │                                                                  │
 * │  1. Instale o driver ODBC da InterSystems:                       │
 * │     https://www.intersystems.com/products/cache/odbc-driver/     │
 * │  2. Configure o DSN no painel ODBC do Windows/Linux             │
 * │  3. No .env, defina CACHE_MODE=odbc                             │
 * │     e preencha CACHE_DSN, CACHE_USER, CACHE_PASS                │
 * │                                                                  │
 * │  Em desenvolvimento local (sem Caché), CACHE_MODE=sqlite        │
 * │  usa um banco SQLite local com a MESMA estrutura SQL            │
 * └─────────────────────────────────────────────────────────────────┘
 * 
 * A SINTAXE SQL usada aqui é idêntica à do Caché SQL real.
 * A única diferença é o driver de conexão (ODBC vs SQLite PDO).
 * 
 * No InterSystems Caché, as tabelas ficam em namespaces e o SQL 
 * padrão é acessado assim:
 *   SELECT * FROM Saude.Paciente WHERE Email = ?
 * 
 * Onde "Saude" é o Schema/Namespace e "Paciente" é a classe/tabela.
 */

class CacheConnection
{
    private ?PDO $pdo = null;
    private string $mode;
    private string $erro = '';

    public function __construct()
    {
        // Pega o modo do ambiente
        $this->mode = $_ENV['CACHE_MODE'] ?? $_ENV['APP_ENV'] === 'production' ? 'pgsql' : 'sqlite';
        
        try {
            if ($this->mode === 'pgsql') {
                $this->conectarPostgreSQL();
            } else {
                $this->conectarSQLite();
            }
        } catch (Exception $e) {
            $this->erro = $e->getMessage();
            error_log("Erro na conexão com banco: " . $this->erro);
            
            // Fallback: tenta SQLite se PostgreSQL falhar
            if ($this->mode === 'pgsql') {
                error_log("Fallback para SQLite devido a erro no PostgreSQL");
                $this->mode = 'sqlite';
                $this->conectarSQLite();
            } else {
                throw $e;
            }
        }
    }
    
    /**
     * Conexão com PostgreSQL (Render)
     */
    private function conectarPostgreSQL(): void
    {
        // Usa DATABASE_URL do Render ou constrói a partir de variáveis individuais
        $dbUrl = $_ENV['DATABASE_URL'] ?? null;
        
        if ($dbUrl) {
            // Formato: postgresql://usuario:senha@host:porta/banco
            $this->pdo = new PDO($dbUrl);
        } else {
            // Fallback para conexão tradicional
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '5432';
            $name = $_ENV['DB_NAME'] ?? 'cache_db';
            $user = $_ENV['DB_USER'] ?? 'postgres';
            $pass = $_ENV['DB_PASS'] ?? '';
            
            $dsn = "pgsql:host=$host;port=$port;dbname=$name;";
            $this->pdo = new PDO($dsn, $user, $pass);
        }
        
        // Configurações do PDO
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Cria as tabelas se não existirem (para PostgreSQL)
        $this->criarTabelasPostgreSQL();
    }
    
    /**
     * Conexão com SQLite (desenvolvimento local)
     */
    private function conectarSQLite(): void
    {
        $dbPath = __DIR__ . '/../cache_dev.sqlite';
        
        // Cria o diretório se não existir
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        $this->pdo = new PDO("sqlite:$dbPath", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        $this->criarTabelasSQLite();
    }
    
    /**
     * Cria tabelas no PostgreSQL
     */
    private function criarTabelasPostgreSQL(): void
    {
        // Cria schema se não existir
        $this->pdo->exec("CREATE SCHEMA IF NOT EXISTS Saude");
        
        // Tabela de pacientes
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS Saude.Paciente (
                PacienteId  SERIAL PRIMARY KEY,
                Nome        VARCHAR(100) NOT NULL,
                Email       VARCHAR(150) NOT NULL UNIQUE,
                Telefone    VARCHAR(20),
                CriadoEm    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela de log de mensagens
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS Saude.LogMensagem (
                LogId       SERIAL PRIMARY KEY,
                PacienteId  INTEGER NOT NULL REFERENCES Saude.Paciente(PacienteId),
                Destinatario VARCHAR(150) NOT NULL,
                EnviadoEm   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Verifica se precisa popular com dados iniciais
        $count = $this->pdo->query("SELECT COUNT(*) FROM Saude.Paciente")->fetchColumn();
        if ((int)$count === 0) {
            $this->pdo->exec("
                INSERT INTO Saude.Paciente (Nome, Email, Telefone) VALUES
                ('Dr. João Silva',     'joao.silva@hospital.com',   '(21) 99999-1111'),
                ('Dra. Maria Santos',  'maria.santos@hospital.com', '(21) 99999-2222'),
                ('Carlos Oliveira',    'carlos.oliveira@gmail.com', '(21) 99999-3333')
            ");
        }
    }
    
    /**
     * Cria tabelas no SQLite (desenvolvimento)
     */
    private function criarTabelasSQLite(): void
    {
        // SQLite não suporta schemas, então usamos nomes sem ponto
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS Saude_Paciente (
                PacienteId  INTEGER PRIMARY KEY AUTOINCREMENT,
                Nome        TEXT    NOT NULL,
                Email       TEXT    NOT NULL UNIQUE,
                Telefone    TEXT,
                CriadoEm    TEXT    DEFAULT (datetime('now'))
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS Saude_LogMensagem (
                LogId       INTEGER PRIMARY KEY AUTOINCREMENT,
                PacienteId  INTEGER NOT NULL,
                Destinatario TEXT NOT NULL,
                EnviadoEm   TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (PacienteId) REFERENCES Saude_Paciente(PacienteId)
            )
        ");

        // Popula com dados de exemplo
        $count = $this->pdo->query("SELECT COUNT(*) FROM Saude_Paciente")->fetchColumn();
        if ((int)$count === 0) {
            $this->pdo->exec("
                INSERT INTO Saude_Paciente (Nome, Email, Telefone) VALUES
                ('Dr. João Silva',     'joao.silva@hospital.com',   '(21) 99999-1111'),
                ('Dra. Maria Santos',  'maria.santos@hospital.com', '(21) 99999-2222'),
                ('Carlos Oliveira',    'carlos.oliveira@gmail.com', '(21) 99999-3333')
            ");
        }
    }

    /**
     * Retorna a PDO para uso nas queries
     */
    public function getPDO(): PDO
    {
        if (!$this->pdo) {
            throw new Exception("Banco de dados não disponível: " . $this->erro);
        }
        return $this->pdo;
    }

    /**
     * Retorna o nome da tabela correto para o modo atual
     */
    public function tabela(string $nomeLogico): string
    {
        if ($this->mode === 'pgsql') {
            // PostgreSQL: mantém o ponto (schema.tabela)
            return $nomeLogico;
        }
        // SQLite: substitui ponto por underscore
        return str_replace('.', '_', $nomeLogico);
    }

    public function isDevMode(): bool
    {
        return $this->mode !== 'pgsql';
    }
    
    public function isDisponivel(): bool
    {
        return $this->pdo !== null;
    }
    
    public function getErro(): string
    {
        return $this->erro;
    }
}
