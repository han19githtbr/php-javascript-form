<?php
/**
 * mail.php  (versão com SendGrid API HTTP + integração ao Caché)
 *
 * Usa a API REST do SendGrid em vez de SMTP, o que funciona em
 * qualquer plataforma de hospedagem (Render, Railway, Heroku, etc.)
 * pois não depende de portas SMTP abertas.
 *
 * Após enviar o e-mail com sucesso, este arquivo:
 *   1. Verifica se o remetente já existe no banco Caché/SQLite
 *   2. Se não existir, cadastra o paciente automaticamente
 *   3. Registra um log da mensagem enviada na tabela Saude.LogMensagem
 */
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require __DIR__ . '/../vendor/autoload.php';

// ── Carrega variáveis de ambiente ─────────────────────────────────
// Tenta .env local primeiro (desenvolvimento), depois usa as variáveis
// já presentes no ambiente (Render, Railway, Docker --env-file, etc.)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Se as variáveis críticas ainda estiverem vazias, tenta getenv()
// (alguns hosts expõem via getenv mas não via $_ENV)
foreach (['MAIL_PASSWORD', 'MAIL_FROM', 'MAIL_FROM_NAME', 'MAIL_USERNAME'] as $var) {
    if (empty($_ENV[$var]) && getenv($var) !== false) {
        $_ENV[$var] = getenv($var);
    }
}

require __DIR__ . '/../Model/CacheConnection.php';

// ── Dados do formulário ───────────────────────────────────────────
$nomeUsuario     = trim($_POST['nome']      ?? '');
$mensagemUsuario = trim($_POST['mensagem']  ?? '');
$emailUsuario    = trim($_POST['correio']   ?? '');
$emailDestino    = trim($_POST['email']     ?? '');

if (empty($nomeUsuario) || empty($mensagemUsuario) || empty($emailUsuario) || empty($emailDestino)) {
    echo json_encode(['error' => true, 'mensagem' => 'Por favor, preencha todos os campos.']);
    exit;
}

// ── Configurações do SendGrid ─────────────────────────────────────
$sendgridKey    = $_ENV['MAIL_PASSWORD']  ?? '';
$remetenteEmail = $_ENV['MAIL_FROM']      ?? $_ENV['MAIL_USERNAME'] ?? '';
$remetenteNome  = $_ENV['MAIL_FROM_NAME'] ?? 'Sistema de Contato';

// Diagnóstico: loga o estado das variáveis (nunca expõe a key completa)
error_log("[mail.php] MAIL_PASSWORD: "  . (empty($sendgridKey)    ? 'VAZIA'  : 'ok (' . substr($sendgridKey, 0, 12)    . '...)'));
error_log("[mail.php] MAIL_FROM: "      . (empty($remetenteEmail) ? 'VAZIA'  : $remetenteEmail));
error_log("[mail.php] MAIL_FROM_NAME: " . (empty($remetenteNome)  ? 'VAZIA'  : $remetenteNome));

if (empty($sendgridKey)) {
    echo json_encode(['error' => true, 'mensagem' => 'Variável MAIL_PASSWORD não encontrada no servidor. Configure-a no painel do Render.']);
    exit;
}

if (empty($remetenteEmail)) {
    echo json_encode(['error' => true, 'mensagem' => 'Variável MAIL_FROM não encontrada no servidor. Configure-a no painel do Render.']);
    exit;
}

// ── Corpo do e-mail ───────────────────────────────────────────────
$corpoHtml = "
    <div style='font-family:sans-serif;max-width:600px;margin:0 auto;background:#343a40;padding:20px;border-radius:8px;'>
        <div style='background:#cce5ff;border:1px solid #b8daff;border-radius:4px;padding:12px 20px;margin-bottom:20px;font-size:1.2em;'>
            <strong>Mensagem de:</strong> " . htmlspecialchars($nomeUsuario) . "
        </div>
        <div style='color:#eee;font-size:18px;margin-bottom:30px;'>
            " . nl2br(htmlspecialchars($mensagemUsuario)) . "
        </div>
        <div style='background:#48494a;color:#ddd;text-align:center;padding:10px;font-size:14px;'>
            Pode responder para: <span style='text-decoration:underline;'>" . htmlspecialchars($emailUsuario) . "</span>
        </div>
    </div>
";

$payload = [
    'personalizations' => [[
        'to' => [['email' => $emailDestino]],
    ]],
    'from'     => ['email' => $remetenteEmail, 'name' => $remetenteNome],
    'reply_to' => ['email' => $emailUsuario,   'name' => $nomeUsuario],
    'subject'  => 'Mensagem de Contato - ' . $nomeUsuario,
    'content'  => [
        ['type' => 'text/plain', 'value' => "De: $nomeUsuario\n\n$mensagemUsuario\n\nResponda para: $emailUsuario"],
        ['type' => 'text/html',  'value' => $corpoHtml],
    ],
];

// ── Chamada à API do SendGrid ─────────────────────────────────────
$ch = curl_init('https://api.sendgrid.com/v3/mail/send');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $sendgridKey,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT        => 30,
]);

$resposta   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErro   = curl_error($ch);
curl_close($ch);

error_log("[mail.php] SendGrid HTTP status: $httpStatus");
error_log("[mail.php] SendGrid response: $resposta");

// SendGrid retorna 202 Accepted em caso de sucesso
if ($httpStatus !== 202) {
    // Extrai a mensagem de erro real do SendGrid
    $erroSg  = '';
    $decoded = json_decode($resposta, true);
    if (!empty($decoded['errors'])) {
        $erroSg = implode(' | ', array_column($decoded['errors'], 'message'));
    }

    echo json_encode([
        'error'    => true,
        'mensagem' => 'Erro SendGrid: ' . ($erroSg ?: "HTTP $httpStatus"),
        'detalhes' => "HTTP $httpStatus" . ($curlErro ? " | cURL: $curlErro" : ''),
    ]);
    exit;
}

// ── Registrar no banco ────────────────────────────────────────────
$cacheInfo = '';
try {
    $cache = new CacheConnection();
    $pdo   = $cache->getPDO();
    $tPac  = $cache->tabela('Saude.Paciente');
    $tLog  = $cache->tabela('Saude.LogMensagem');

    $stmt = $pdo->prepare("SELECT PacienteId FROM $tPac WHERE Email = :email");
    $stmt->execute([':email' => $emailUsuario]);
    $paciente = $stmt->fetch();

    if ($paciente) {
        $pacienteId = $paciente['PacienteId'];
        $cacheInfo  = '✅ Paciente já registrado (ID: ' . $pacienteId . ')';
    } else {
        $insert = $pdo->prepare("INSERT INTO $tPac (Nome, Email) VALUES (:nome, :email)");
        $insert->execute([':nome' => $nomeUsuario, ':email' => $emailUsuario]);
        $pacienteId = $pdo->lastInsertId();
        $cacheInfo  = '✅ Paciente cadastrado (ID: ' . $pacienteId . ')';
    }

    $log = $pdo->prepare("INSERT INTO $tLog (PacienteId, Destinatario) VALUES (:id, :dest)");
    $log->execute([':id' => $pacienteId, ':dest' => $emailDestino]);

} catch (Exception $e) {
    $cacheInfo = '⚠️ Email enviado, mas não foi possível registrar no banco.';
    error_log("[mail.php] Erro banco: " . $e->getMessage());
}

echo json_encode([
    'error'      => false,
    'mensagem'   => 'Mensagem enviada com sucesso!',
    'cache_info' => $cacheInfo,
]);
