<?php
header('Content-Type: application/json');

// Tratamento de erros
set_error_handler(function($errno, $errstr) {
    echo json_encode(['encontrado' => false, 'erro' => $errstr]);
    exit;
});

require __DIR__ . '/../vendor/autoload.php';

// Carrega .env apenas localmente
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
    
    if (!$cache->isDisponivel()) {
        echo json_encode([
            'encontrado' => false, 
            'erro' => 'Banco de dados temporariamente indisponível'
        ]);
        exit;
    }
    
    $pdo    = $cache->getPDO();
    $tabela = $cache->tabela('Saude.Paciente');

    // Query adaptada para PostgreSQL/SQLite
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
            'fonte'      => $cache->isDevMode() ? 'SQLite (desenvolvimento)' : 'PostgreSQL (produção)',
        ]);
    } else {
        echo json_encode(['encontrado' => false]);
    }

} catch (Exception $e) {
    echo json_encode([
        'encontrado' => false, 
        'erro' => 'Erro na consulta: ' . $e->getMessage()
    ]);
}