<?php
/**
 * buscar-paciente.php
 * 
 * Endpoint chamado pelo JavaScript ao digitar o e-mail no formulário.
 * Consulta o banco Caché (ou SQLite em dev) e retorna os dados do paciente.
 * 
 * Rota: GET /php-javascript-form/Model/buscar-paciente.php?email=...
 * 
 * ── QUERY EQUIVALENTE NO CACHÉ REAL (via ODBC ou ObjectScript) ────
 * 
 *   SELECT PacienteId, Nome, Email, Telefone
 *   FROM Saude.Paciente
 *   WHERE Email = :email
 * 
 *   No ObjectScript ficaria assim:
 *   &SQL(SELECT Nome INTO :nome FROM Saude.Paciente WHERE Email = :email)
 * ──────────────────────────────────────────────────────────────────
 */

header('Content-Type: application/json');
ini_set('display_errors', 0);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/../Model/CacheConnection.php';

$email = trim($_GET['email'] ?? '');

if (empty($email)) {
    echo json_encode(['encontrado' => false, 'erro' => 'E-mail não informado.']);
    exit;
}

try {
    $cache  = new CacheConnection();
    $pdo    = $cache->getPDO();
    $tabela = $cache->tabela('Saude.Paciente');

    // Esta query SQL é idêntica à que rodaria no Caché real via ODBC
    $stmt = $pdo->prepare("
        SELECT PacienteId, Nome, Email, Telefone
        FROM $tabela
        WHERE Email = :email
    ");
    $stmt->execute([':email' => $email]);
    $paciente = $stmt->fetch();

    if ($paciente) {
        echo json_encode([
            'encontrado' => true,
            'paciente'   => $paciente,
            'fonte'      => $cache->isDevMode() ? 'SQLite (simulação Caché)' : 'InterSystems Caché',
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }

} catch (Exception $e) {
    echo json_encode(['encontrado' => false, 'erro' => $e->getMessage()]);
}
