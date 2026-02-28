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
    private PDO $pdo;
    private string $mode;

    public function __construct()
    {
        $this->mode = $_ENV['CACHE_MODE'] ?? 'sqlite';

        if ($this->mode === 'odbc') {
            // ── CONEXÃO REAL COM INTERSYSTEMS CACHÉ ──────────────────────
            // Requer: driver ODBC da InterSystems + DSN configurado
            // Documentação: https://docs.intersystems.com/latest/csp/docbook/DocBook.UI.Page.cls?KEY=BNETODBC
            $dsn  = $_ENV['CACHE_DSN']  ?? 'CacheODBC';  // Nome do DSN ODBC configurado
            $user = $_ENV['CACHE_USER'] ?? '_SYSTEM';    // Usuário padrão do Caché
            $pass = $_ENV['CACHE_PASS'] ?? 'SYS';        // Senha padrão (dev only)

            $this->pdo = new PDO("odbc:$dsn", $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            // ── MODO DESENVOLVIMENTO: SQLite simula o Caché ───────────────
            // Mesma estrutura SQL, sem precisar instalar o Caché
            $dbPath   = __DIR__ . '/../cache_dev.sqlite';
            $this->pdo = new PDO("sqlite:$dbPath", null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->criarTabelasDevMode();
        }
    }

    /**
     * Cria as tabelas no SQLite (dev mode).
     * A estrutura SQL é idêntica à que seria criada no Caché real.
     * 
     * No Caché real, você criaria via Management Portal ou ObjectScript:
     *   CREATE TABLE Saude.Paciente (
     *       PacienteId  INTEGER NOT NULL,
     *       Nome        VARCHAR(100) NOT NULL,
     *       Email       VARCHAR(150) NOT NULL,
     *       Telefone    VARCHAR(20),
     *       CriadoEm   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     *       CONSTRAINT PK_Paciente PRIMARY KEY (PacienteId)
     *   )
     */
    private function criarTabelasDevMode(): void
    {
        // SQLite não suporta schemas (Saude.Paciente), então usamos
        // o nome de tabela composto. No Caché real, o schema é separado.
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
                LogId       INTEGER PRIMARY KEY AUTOINCREMENT,
                PacienteId  INTEGER NOT NULL,
                Destinatario TEXT NOT NULL,
                EnviadoEm   TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (PacienteId) REFERENCES Saude_Paciente(PacienteId)
            )
        ");

        // Popula com pacientes de exemplo na primeira execução
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
     * Retorna a PDO para uso direto nas queries.
     * No Caché real, esta mesma PDO viria via ODBC.
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * Retorna o nome da tabela correto para o modo atual.
     * 
     * Caché real : "Saude.Paciente"   (schema.tabela em ObjectScript)
     * SQLite dev  : "Saude_Paciente"  (sem suporte a schemas)
     */
    public function tabela(string $nomeLogico): string
    {
        if ($this->mode === 'odbc') {
            // No Caché, o separador de schema é ponto: Saude.Paciente
            return $nomeLogico;
        }
        // No SQLite local, trocamos ponto por underscore
        return str_replace('.', '_', $nomeLogico);
    }

    public function isDevMode(): bool
    {
        return $this->mode !== 'odbc';
    }
}
