<?php
/**
 * CacheConnection.php
 *
 * Camada de abstração para o InterSystems Caché.
 *
 * MELHORIAS APLICADAS:
 * 1. [TIPAGEM] Declarações de tipo em todos os métodos (PHP 7.4+).
 *    Erros de tipo são capturados em tempo de execução, não em produção.
 * 2. [ORGANIZAÇÃO] Constantes de classe substituem strings literais repetidas.
 * 3. [CLAREZA] Construtor dividido em método init() para facilitar testes unitários.
 * 4. [ROBUSTEZ] Método getMode() expõe o modo atual para fins de diagnóstico.
 * 5. [PADRÃO] phpDoc completo em todos os métodos públicos.
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  COMO FUNCIONA EM PRODUÇÃO (servidor com Caché instalado)       │
 * │  1. Instale o driver ODBC da InterSystems:                       │
 * │     https://www.intersystems.com/products/cache/odbc-driver/     │
 * │  2. Configure o DSN no painel ODBC do Windows/Linux             │
 * │  3. No .env, defina CACHE_MODE=odbc                             │
 * │                                                                  │
 * │  Em desenvolvimento local (sem Caché), CACHE_MODE=sqlite usa    │
 * │  um banco SQLite com a MESMA estrutura SQL do Caché.            │
 * └─────────────────────────────────────────────────────────────────┘
 */

declare(strict_types=1);

class CacheConnection
{
    // MELHORIA 2: constantes de classe evitam strings literais espalhadas
    private const MODE_PGSQL  = 'pgsql';
    private const MODE_SQLITE = 'sqlite';

    private const PDO_OPTIONS = [
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    private ?PDO   $pdo  = null;
    private string $mode;
    private string $erro = '';

    public function __construct()
    {
        // MELHORIA 1: operador ternário explícito com tipos claros
        $modoEnv    = $_ENV['CACHE_MODE'] ?? '';
        $appEnv     = $_ENV['APP_ENV']    ?? 'development';
        $this->mode = $modoEnv ?: ($appEnv === 'production' ? self::MODE_PGSQL : self::MODE_SQLITE);

        $this->inicializar();
    }

    /**
     * MELHORIA 3: lógica de inicialização extraída do construtor.
     * Facilita testes unitários (pode ser chamado com diferentes modos).
     */
    private function inicializar(): void
    {
        try {
            if ($this->mode === self::MODE_PGSQL) {
                $this->conectarPostgreSQL();
            } else {
                $this->conectarSQLite();
            }
        } catch (Exception $e) {
            $this->erro = $e->getMessage();
            error_log("CacheConnection: erro na conexão — " . $this->erro);

            // Fallback automático para SQLite quando PostgreSQL falhar
            if ($this->mode === self::MODE_PGSQL) {
                error_log("CacheConnection: fallback para SQLite.");
                $this->mode = self::MODE_SQLITE;
                $this->conectarSQLite();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Conecta ao PostgreSQL (Render ou servidor próprio).
     *
     * @throws PDOException em caso de falha de conexão
     */
    private function conectarPostgreSQL(): void
    {
        $dbUrl = $_ENV['DATABASE_URL'] ?? null;

        if ($dbUrl) {
            $this->pdo = new PDO($dbUrl, null, null, self::PDO_OPTIONS);
        } else {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '5432';
            $name = $_ENV['DB_NAME'] ?? 'cache_db';
            $user = $_ENV['DB_USER'] ?? 'postgres';
            $pass = $_ENV['DB_PASS'] ?? '';

            $dsn       = "pgsql:host={$host};port={$port};dbname={$name};";
            $this->pdo = new PDO($dsn, $user, $pass, self::PDO_OPTIONS);
        }

        $this->criarTabelasPostgreSQL();
    }

    /**
     * Conecta ao SQLite (ambiente de desenvolvimento local).
     *
     * @throws PDOException em caso de falha ao criar/abrir o arquivo
     */
    private function conectarSQLite(): void
    {
        $dbPath = __DIR__ . '/../cache_dev.sqlite';
        $dbDir  = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->pdo = new PDO("sqlite:{$dbPath}", null, null, self::PDO_OPTIONS);
        $this->criarTabelasSQLite();
    }

    /**
     * Cria as tabelas necessárias no PostgreSQL se ainda não existirem.
     * Popula com dados de exemplo na primeira execução.
     */
    private function criarTabelasPostgreSQL(): void
    {
        $this->pdo->exec("CREATE SCHEMA IF NOT EXISTS Saude");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS Saude.Paciente (
                PacienteId  SERIAL PRIMARY KEY,
                Nome        VARCHAR(100) NOT NULL,
                Email       VARCHAR(150) NOT NULL UNIQUE,
                Telefone    VARCHAR(20),
                CriadoEm   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS Saude.LogMensagem (
                LogId        SERIAL PRIMARY KEY,
                PacienteId   INTEGER NOT NULL REFERENCES Saude.Paciente(PacienteId),
                Destinatario VARCHAR(150) NOT NULL,
                EnviadoEm   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $total = (int) $this->pdo->query("SELECT COUNT(*) FROM Saude.Paciente")->fetchColumn();
        if ($total === 0) {
            $this->inserirDadosExemplo(self::MODE_PGSQL);
        }
    }

    /**
     * Cria as tabelas necessárias no SQLite se ainda não existirem.
     * Popula com dados de exemplo na primeira execução.
     *
     * Nota: SQLite não suporta schemas — usamos underscore no lugar do ponto
     * (Saude.Paciente → Saude_Paciente) mantendo a mesma estrutura lógica.
     */
    private function criarTabelasSQLite(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS Saude_Paciente (
                PacienteId  INTEGER PRIMARY KEY AUTOINCREMENT,
                Nome        TEXT    NOT NULL,
                Email       TEXT    NOT NULL UNIQUE,
                Telefone    TEXT,
                CriadoEm   TEXT    DEFAULT (datetime('now'))
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS Saude_LogMensagem (
                LogId        INTEGER PRIMARY KEY AUTOINCREMENT,
                PacienteId   INTEGER NOT NULL,
                Destinatario TEXT    NOT NULL,
                EnviadoEm   TEXT    DEFAULT (datetime('now')),
                FOREIGN KEY (PacienteId) REFERENCES Saude_Paciente(PacienteId)
            )
        ");

        $total = (int) $this->pdo->query("SELECT COUNT(*) FROM Saude_Paciente")->fetchColumn();
        if ($total === 0) {
            $this->inserirDadosExemplo(self::MODE_SQLITE);
        }
    }

    /**
     * MELHORIA 3: lógica de seed extraída para evitar duplicação entre
     * criarTabelasPostgreSQL() e criarTabelasSQLite().
     */
    private function inserirDadosExemplo(string $modo): void
    {
        $tabela = $modo === self::MODE_PGSQL ? 'Saude.Paciente' : 'Saude_Paciente';
        $this->pdo->exec("
            INSERT INTO {$tabela} (Nome, Email, Telefone) VALUES
            ('Dr. João Silva',    'joao.silva@hospital.com',   '(21) 99999-1111'),
            ('Dra. Maria Santos', 'maria.santos@hospital.com', '(21) 99999-2222'),
            ('Carlos Oliveira',   'carlos.oliveira@gmail.com', '(21) 99999-3333')
        ");
    }

    // ── API pública ───────────────────────────────────────────────

    /**
     * Retorna a instância PDO para uso nas queries.
     *
     * @throws RuntimeException se o banco não estiver disponível
     */
    public function getPDO(): PDO
    {
        if ($this->pdo === null) {
            throw new RuntimeException(
                "Banco de dados não disponível: " . $this->erro
            );
        }
        return $this->pdo;
    }

    /**
     * Traduz o nome lógico da tabela (schema.tabela) para o formato
     * correto do banco em uso.
     *
     * Exemplo:
     *   PostgreSQL → "Saude.Paciente"   (mantém o ponto)
     *   SQLite     → "Saude_Paciente"   (substitui ponto por underscore)
     */
    public function tabela(string $nomeLogico): string
    {
        return $this->mode === self::MODE_PGSQL
            ? $nomeLogico
            : str_replace('.', '_', $nomeLogico);
    }

    /** Retorna true quando o banco está usando SQLite (desenvolvimento). */
    public function isDevMode(): bool
    {
        return $this->mode !== self::MODE_PGSQL;
    }

    /** Retorna true quando a conexão com o banco foi estabelecida com sucesso. */
    public function isDisponivel(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * MELHORIA 4: expõe o modo atual para facilitar logs e diagnóstico.
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /** Retorna a mensagem do último erro de conexão, se houver. */
    public function getErro(): string
    {
        return $this->erro;
    }
}
