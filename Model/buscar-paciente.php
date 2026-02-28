<?php
/**
 * buscar-paciente.php — Endpoint de consulta de paciente por e-mail
 *
 * MELHORIAS APLICADAS:
 * 1. [SEGURANÇA] Validação do e-mail com filter_var antes de qualquer acesso ao banco.
 * 2. [SEGURANÇA] Apenas campos necessários são retornados ao front-end (sem PacienteId).
 * 3. [ORGANIZAÇÃO] Lógica de consulta extraída para função nomeada.
 * 4. [ROBUSTEZ]  Distinção entre "banco indisponível" e "paciente não encontrado".
 * 5. [PADRÃO]    Resposta JSON padronizada com a mesma estrutura de mail.php.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

set_error_handler(function (int $errno, string $errstr): bool {
    responderBusca(false, null, 'Erro interno: ' . $errstr);
    return true;
});

require __DIR__ . '/../vendor/autoload.php';

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

require __DIR__ . '/../Model/CacheConnection.php';

// ── Valida o parâmetro recebido ───────────────────────────────────
// MELHORIA 1: filter_var antes de qualquer processamento
$email = trim($_GET['email'] ?? '');

if (empty($email)) {
    responderBusca(false, null, 'E-mail não informado.');
}

// MELHORIA 1: rejeita e-mails com formato inválido antes de consultar o banco
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderBusca(false, null, 'Formato de e-mail inválido.');
}

// ── Consulta o banco ──────────────────────────────────────────────
try {
    $cache = new CacheConnection();

    // MELHORIA 4: banco indisponível é um cenário de erro distinto de "não encontrado"
    if (!$cache->isDisponivel()) {
        responderBusca(false, null, 'Banco de dados temporariamente indisponível.');
    }

    $paciente = buscarPacientePorEmail($cache, $email);

    if ($paciente !== null) {
        responderBusca(
            true,
            $paciente,
            null,
            $cache->isDevMode() ? 'SQLite (desenvolvimento)' : 'PostgreSQL (produção)'
        );
    }

    responderBusca(false, null);

} catch (Exception $e) {
    error_log("[buscar-paciente.php] " . $e->getMessage());
    responderBusca(false, null, 'Erro na consulta ao banco de dados.');
}


// ══════════════════════════════════════════════════════════════════
// FUNÇÕES
// ══════════════════════════════════════════════════════════════════

/**
 * Consulta um paciente pelo e-mail no banco de dados.
 *
 * MELHORIA 2: a query seleciona somente as colunas que o front-end
 * precisa — evita expor PacienteId e outros campos sensíveis.
 *
 * @return array<string,string>|null Dados do paciente ou null se não encontrado
 */
function buscarPacientePorEmail(CacheConnection $cache, string $email): ?array
{
    $pdo    = $cache->getPDO();
    $tabela = $cache->tabela('Saude.Paciente');

    // MELHORIA 2: seleciona apenas Nome e Telefone (sem PacienteId)
    $stmt = $pdo->prepare("
        SELECT Nome, Telefone
        FROM {$tabela}
        WHERE Email = :email
    ");
    $stmt->execute([':email' => $email]);

    $resultado = $stmt->fetch();
    return $resultado !== false ? $resultado : null;
}

/**
 * Emite a resposta JSON padronizada e encerra a execução.
 *
 * MELHORIA 5: mesma estrutura de resposta de mail.php — o front-end
 * lida com um único padrão em todos os endpoints.
 *
 * @param array<string,string>|null $paciente
 */
function responderBusca(
    bool    $encontrado,
    ?array  $paciente,
    ?string $erro  = null,
    ?string $fonte = null
): never {
    $payload = ['encontrado' => $encontrado];

    if ($encontrado && $paciente !== null) {
        $payload['paciente'] = $paciente;
        $payload['fonte']    = $fonte ?? 'banco de dados';
    }

    if ($erro !== null) {
        $payload['erro'] = $erro;
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}