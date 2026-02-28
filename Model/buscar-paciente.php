<?php
header('Content-Type: application/json');

// Captura qualquer erro fatal e retorna como JSON
set_error_handler(function($errno, $errstr) {
    echo json_encode(['encontrado' => false, 'erro' => $errstr]);
    exit;
});

require __DIR__ . '/../vendor/autoload.php';

// No Render as variáveis já são injetadas pelo painel.
// O .env só existe localmente (XAMPP). Carregamos só se existir.
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

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
            'fonte'      => $cache->isDevMode() ? 'SQLite (simulação Caché)' : 'PostgreSQL (Render)',
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }

} catch (Exception $e) {
    echo json_encode(['encontrado' => false, 'erro' => $e->getMessage()]);
}
