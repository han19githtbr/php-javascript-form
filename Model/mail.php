<?php
/**
 * mail.php  (versão Resend API + integração ao Caché)
 */
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require __DIR__ . '/../vendor/autoload.php';

// ── Carrega variáveis de ambiente ─────────────────────────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Fallback para getenv() (necessário em alguns hosts como Render)
foreach (['MAIL_PASSWORD', 'MAIL_FROM', 'MAIL_FROM_NAME'] as $var) {
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

// ── Configurações do Resend ───────────────────────────────────────
$resendKey      = $_ENV['MAIL_PASSWORD']  ?? '';
$remetenteEmail = $_ENV['MAIL_FROM']      ?? 'onboarding@resend.dev';
$remetenteNome  = $_ENV['MAIL_FROM_NAME'] ?? 'Sistema de Contato';

error_log("[mail.php] MAIL_PASSWORD: " . (empty($resendKey)      ? 'VAZIA' : 'ok (' . substr($resendKey, 0, 10) . '...)'));
error_log("[mail.php] MAIL_FROM: "     . (empty($remetenteEmail) ? 'VAZIA' : $remetenteEmail));

if (empty($resendKey)) {
    echo json_encode(['error' => true, 'mensagem' => 'Variável MAIL_PASSWORD (Resend API key) não encontrada no servidor.']);
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

// ── Payload da API do Resend ──────────────────────────────────────
$payload = [
    'from'     => $remetenteNome . ' <' . $remetenteEmail . '>',
    'to'       => [$emailDestino],
    'reply_to' => $emailUsuario,
    'subject'  => 'Mensagem de Contato - ' . $nomeUsuario,
    'text'     => "De: $nomeUsuario\n\n$mensagemUsuario\n\nResponda para: $emailUsuario",
    'html'     => $corpoHtml,
];

// ── Chamada à API do Resend ───────────────────────────────────────
$ch = curl_init('https://api.resend.com/emails');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $resendKey,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT        => 30,
]);

$resposta   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErro   = curl_error($ch);
curl_close($ch);

error_log("[mail.php] Resend HTTP status: $httpStatus");
error_log("[mail.php] Resend response: $resposta");

// Resend retorna 200 em caso de sucesso
if ($httpStatus !== 200) {
    $decoded = json_decode($resposta, true);
    $erroMsg = $decoded['message'] ?? $decoded['name'] ?? "HTTP $httpStatus";

    echo json_encode([
        'error'    => true,
        'mensagem' => 'Erro Resend: ' . $erroMsg,
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

